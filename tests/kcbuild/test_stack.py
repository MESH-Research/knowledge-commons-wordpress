from kcbuild import stack
from tests.kcbuild.support import FakeRunner
from tests.kcbuild.support import answer

COMPOSE = ["docker", "compose", "-f", "docker-compose.test.yml"]
OVERRIDE = ["-f", "local.yml"]


def test_compose_command_targets_the_test_stack():
    assert stack.compose_command() == COMPOSE


def test_every_override_file_is_appended():
    assert stack.compose_command(["a.yml", "b.yml"]) == [
        *COMPOSE,
        "-f",
        "a.yml",
        "-f",
        "b.yml",
    ]


def test_build_builds_the_whole_stack_or_named_services(tmp_path):
    fake = FakeRunner()

    stack.stack_build(fake, tmp_path)
    stack.stack_build(fake, tmp_path, services=["app"])

    assert fake.commands == [[*COMPOSE, "build"], [*COMPOSE, "build", "app"]]


def test_up_builds_then_starts_the_web_services(tmp_path):
    fake = FakeRunner()

    stack.stack_up(fake, tmp_path, ["local.yml"])

    assert fake.commands == [
        [*COMPOSE, *OVERRIDE, "build"],
        [*COMPOSE, *OVERRIDE, "up", "-d", "db", "app", "nginx"],
    ]


def test_up_can_skip_the_build(tmp_path):
    fake = FakeRunner()

    stack.stack_up(fake, tmp_path, build=False)

    assert fake.commands == [[*COMPOSE, "up", "-d", "db", "app", "nginx"]]


def test_setup_runs_wordpress_then_buddypress_phases_in_order(tmp_path):
    fake = FakeRunner()

    stack.stack_setup(fake, tmp_path, ["local.yml"])

    setup = fake.index_of(*COMPOSE, *OVERRIDE, "run", "--rm", "setup")
    curls = [
        i
        for i, args in enumerate(fake.commands)
        if args[: len(COMPOSE) + 4] == [*COMPOSE, *OVERRIDE, "exec", "app"]
        and "curl" in args
    ]
    bp = fake.index_of(
        *COMPOSE,
        *OVERRIDE,
        "exec",
        "app",
        "bash",
        "/app/scripts/test-scripts/setup-bp.sh",
    )
    assert len(curls) == 2
    assert setup < curls[0] < curls[1] < bp
    for i in curls:
        assert "http://nginx:80/wp/wp-admin/" in fake.commands[i]
        assert "Host: hcommons.test" in fake.commands[i]


def test_setup_waits_for_php_fpm_before_each_phase(tmp_path):
    fake = FakeRunner()

    stack.stack_setup(fake, tmp_path)

    assert fake.commands_starting("sleep")


def test_e2e_returns_the_playwright_exit_code(tmp_path):
    fake = FakeRunner(answer((*COMPOSE, "run", "--rm", "playwright"), 3, ""))

    assert stack.stack_e2e(fake, tmp_path) == 3
    assert stack.stack_e2e(FakeRunner(), tmp_path) == 0


def test_php_log_dump_reads_the_fatal_log_without_failing(tmp_path):
    fake = FakeRunner(answer((*COMPOSE, "exec", "app", "cat"), 1, ""))

    stack.dump_php_logs(fake, tmp_path)

    (command,) = fake.commands_starting(*COMPOSE, "exec", "app", "cat")
    assert command[-1] == "/tmp/php-fatal.log"


def test_down_removes_orphans_and_optionally_volumes(tmp_path):
    fake = FakeRunner()

    stack.stack_down(fake, tmp_path, ["local.yml"])
    stack.stack_down(fake, tmp_path, volumes=True)

    assert fake.commands == [
        [*COMPOSE, *OVERRIDE, "down", "--remove-orphans"],
        [*COMPOSE, "down", "-v", "--remove-orphans"],
    ]


def test_logs_follow_by_default_and_can_target_services(tmp_path):
    fake = FakeRunner()

    stack.stack_logs(fake, tmp_path)
    stack.stack_logs(fake, tmp_path, services=["app"], follow=False)

    assert fake.commands == [
        [*COMPOSE, "logs", "-f"],
        [*COMPOSE, "logs", "app"],
    ]


def test_exec_runs_in_the_named_service_and_returns_its_status(tmp_path):
    fake = FakeRunner(answer((*COMPOSE, "exec", "nginx"), 2, ""))

    status = stack.stack_exec(
        fake,
        tmp_path,
        None,
        "nginx",
        ["nginx", "-t"],
        check=False,
    )

    assert status == 2
    assert fake.commands == [[*COMPOSE, "exec", "nginx", "nginx", "-t"]]


def test_wp_cli_runs_in_the_app_container_as_root(tmp_path):
    fake = FakeRunner()

    stack.stack_wp(fake, tmp_path, ["local.yml"], ["plugin", "list"])

    assert fake.commands == [
        [
            *COMPOSE,
            *OVERRIDE,
            "exec",
            "app",
            "wp",
            "--allow-root",
            "plugin",
            "list",
        ],
    ]
