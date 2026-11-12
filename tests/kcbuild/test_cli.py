from pathlib import Path

import pytest
from click.testing import CliRunner

from kcbuild.cli import main
from tests.kcbuild.support import FakeRunner
from tests.kcbuild.support import answer

SHA = "0123456789abcdef0123456789abcdef01234567"
REG = "755997884632.dkr.ecr.us-east-1.amazonaws.com"
BASE = f"{REG}/commons-wordpress-base:latest"
APP = f"{REG}/commons-wordpress-app"
NGINX = f"{REG}/commons-wordpress-nginx"
VERSION_SCRIPT = "scripts/build-scripts/resolve-build-version.sh"
CZ = ("uvx", "--from", "commitizen==4.16.2", "cz")
COMPOSE = ["docker", "compose", "-f", "docker-compose.test.yml"]
MAIN_PUSHES = 8  # 2 repositories x (branch, build, version, latest)
BRANCH_PUSHES = 4  # 2 repositories x (branch, build)

GIT_SHA = answer(("git", "rev-parse", "HEAD"), 0, f"{SHA}\n")
ON_MAIN = answer(("git", "rev-parse", "--abbrev-ref", "HEAD"), 0, "main\n")
ON_PRODUCTION = answer(
    ("git", "rev-parse", "--abbrev-ref", "HEAD"),
    0,
    "production\n",
)
VERSION_121 = answer(("sh", VERSION_SCRIPT), 0, "1.2.1\n")
VERSION_160 = answer(("sh", VERSION_SCRIPT), 0, "1.6.0\n")
NO_BUMP = answer((*CZ, "bump", "--get-next"), 21, "")
BUMP_TO_130 = answer((*CZ, "bump", "--get-next"), 0, "1.3.0\n")
AWS_OK = answer(("aws", "sts", "get-caller-identity"), 0, "755997884632\n")
AWS_LOGGED_OUT = answer(("aws", "sts", "get-caller-identity"), 255, "")
ECR_TOKEN = answer(("aws", "ecr", "get-login-password"), 0, "tok\n")
BASE_PRESENT = answer(("docker", "image", "inspect"), 0, "[]")
BASE_MISSING = answer(("docker", "image", "inspect"), 1, "")

MAIN = (GIT_SHA, ON_MAIN, VERSION_121, NO_BUMP)
PRODUCTION = (GIT_SHA, ON_PRODUCTION, VERSION_160)


@pytest.fixture
def repo(tmp_path: Path) -> Path:
    (tmp_path / "VERSION").write_text("1.2.1\n")
    return tmp_path


def invoke(fake: FakeRunner, repo: Path, *args: str):
    return CliRunner().invoke(
        main,
        ["--repo", str(repo), *args],
        obj=fake,
        catch_exceptions=False,
    )


def buildx_for(fake: FakeRunner, dockerfile: str) -> list[list[str]]:
    return [
        c
        for c in fake.commands_starting("docker", "buildx", "build")
        if dockerfile in c
    ]


# -- info -----------------------------------------------------------------


def test_info_reports_main_tags_without_touching_docker(repo):
    fake = FakeRunner(*MAIN)

    result = invoke(fake, repo, "info")

    assert result.exit_code == 0, result.output
    assert SHA in result.output
    assert "v1.2.1-0123456" in result.output
    assert f"{APP}:latest" in result.output
    assert f"{NGINX}:v1.2.1" in result.output
    assert not fake.commands_starting("docker")


def test_info_for_a_deployment_branch_prefixes_the_build_tag(repo):
    fake = FakeRunner(*PRODUCTION)

    result = invoke(fake, repo, "info")

    assert result.exit_code == 0, result.output
    assert "production-v1.6.0-0123456" in result.output
    assert f"{APP}:latest" not in result.output
    assert f"{NGINX}:latest" not in result.output
    assert f"{APP}:v1.6.0" not in result.output
    assert not fake.commands_starting("uvx")


def test_info_can_pretend_to_be_another_branch(repo):
    fake = FakeRunner(GIT_SHA, ON_PRODUCTION, answer(("sh",), 0, "1.2.1\n"))

    result = invoke(fake, repo, "--branch", "dev", "info")

    assert result.exit_code == 0, result.output
    assert "dev-v1.2.1-0123456" in result.output
    assert ["sh", VERSION_SCRIPT, "dev"] in fake.commands


def test_info_on_main_shows_the_version_commitizen_would_bump_to(repo):
    fake = FakeRunner(
        GIT_SHA,
        ON_MAIN,
        BUMP_TO_130,
        answer(("sh", VERSION_SCRIPT, "main", "1.3.0"), 0, "1.3.0\n"),
    )

    result = invoke(fake, repo, "info")

    assert result.exit_code == 0, result.output
    assert "v1.3.0-0123456" in result.output
    assert "bump" in result.output.lower()


def test_bump_check_can_be_skipped_on_main(repo):
    fake = FakeRunner(GIT_SHA, ON_MAIN, VERSION_121)

    result = invoke(fake, repo, "--skip-bump", "info")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting("uvx")


# -- lint and test ----------------------------------------------------------


def test_lint_runs_phpcs_through_composer(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "lint")

    assert result.exit_code == 0, result.output
    assert fake.commands == [["composer", "test"]]


def test_test_runs_both_phpunit_suites_by_default(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "test")

    assert result.exit_code == 0, result.output
    assert fake.commands == [
        ["vendor/bin/phpunit", "--configuration=tests/unit/phpunit.xml"],
        [
            "vendor/bin/phpunit",
            "--configuration=mu-plugins/ci-logon/tests/phpunit.xml",
        ],
    ]


def test_test_can_select_one_suite_and_filter(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "test", "--suite", "site", "--filter", "Docs")

    assert result.exit_code == 0, result.output
    (command,) = fake.commands
    assert "--configuration=tests/unit/phpunit.xml" in command
    assert command[-2:] == ["--filter", "Docs"]


def test_test_can_add_the_nginx_redirect_checks(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "test", "--nginx")

    assert result.exit_code == 0, result.output
    assert ["bash", "tests/nginx/test-members-redirects.sh"] in fake.commands
    assert len(fake.commands_starting("vendor/bin/phpunit")) == 2


def test_test_only_nginx_skips_phpunit(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "test", "--nginx", "--suite", "none")

    assert result.exit_code == 0, result.output
    assert fake.commands == [["bash", "tests/nginx/test-members-redirects.sh"]]


def test_failing_tests_exit_non_zero_without_a_traceback(repo):
    fake = FakeRunner(answer(("vendor/bin/phpunit",), 1, ""))

    result = CliRunner().invoke(
        main,
        ["--repo", str(repo), "test"],
        obj=fake,
    )

    assert result.exit_code == 1
    assert "Traceback" not in result.output
    assert "phpunit" in result.output


# -- base image -------------------------------------------------------------


def test_base_builds_the_base_stage_locally_by_default(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "base")

    assert result.exit_code == 0, result.output
    (build,) = fake.commands_starting("docker", "buildx", "build")
    assert build[build.index("--target") + 1] == "base"
    assert build[build.index("-t") + 1] == BASE
    assert not fake.commands_starting("aws")
    assert not fake.commands_starting("docker", "push")


def test_base_pull_fetches_from_ecr_after_login(repo):
    fake = FakeRunner(AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "base", "--pull")

    assert result.exit_code == 0, result.output
    assert fake.commands_starting("docker", "login")
    assert fake.lines[-1] == f"docker pull {BASE}"
    assert not fake.commands_starting("docker", "buildx")


def test_base_push_mirrors_the_build_base_image_workflow(repo):
    fake = FakeRunner(AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "base", "--push")

    assert result.exit_code == 0, result.output
    build = fake.index_of("docker", "buildx", "build")
    login = fake.index_of("docker", "login")
    push = fake.index_of("docker", "push", BASE)
    assert login < build < push


def test_base_refuses_to_push_without_credentials(repo):
    fake = FakeRunner(AWS_LOGGED_OUT)

    result = CliRunner().invoke(
        main,
        ["--repo", str(repo), "base", "--push"],
        obj=fake,
    )

    assert result.exit_code != 0
    assert "Traceback" not in result.output
    assert not fake.commands_starting("docker", "push")


# -- build ----------------------------------------------------------------


def test_build_reuses_a_present_base_and_builds_both_images(repo):
    fake = FakeRunner(*MAIN, BASE_PRESENT)

    result = invoke(fake, repo, "build")

    assert result.exit_code == 0, result.output
    builds = fake.commands_starting("docker", "buildx", "build")
    assert len(builds) == 2
    assert not any("--target" in b and "base" in b for b in builds)
    (nginx,) = buildx_for(fake, "Dockerfile.nginx")
    (app,) = buildx_for(fake, "Dockerfile.php")
    assert f"{NGINX}:v1.2.1-0123456" in nginx
    assert f"{NGINX}:latest" in nginx
    assert f"{APP}:main" in app
    assert f"BASE_IMAGE={BASE}" in app
    assert "APP_ENV=main" in nginx
    assert not fake.commands_starting("docker", "push")
    assert not fake.commands_starting("aws")


def test_build_creates_the_base_image_first_when_missing(repo):
    fake = FakeRunner(*MAIN, BASE_MISSING)

    result = invoke(fake, repo, "build")

    assert result.exit_code == 0, result.output
    builds = fake.commands_starting("docker", "buildx", "build")
    assert len(builds) == 3
    assert builds[0][builds[0].index("--target") + 1] == "base"


def test_build_can_pull_the_base_from_ecr_instead(repo):
    fake = FakeRunner(*MAIN, BASE_MISSING, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "build", "--pull-base")

    assert result.exit_code == 0, result.output
    assert ["docker", "pull", BASE] in fake.commands
    assert len(fake.commands_starting("docker", "buildx", "build")) == 2
    assert fake.index_of("docker", "pull") < fake.index_of(
        "docker",
        "buildx",
        "build",
    )


def test_build_can_be_limited_to_one_image(repo):
    fake = FakeRunner(*MAIN, BASE_PRESENT)

    result = invoke(fake, repo, "build", "--image", "nginx")

    assert result.exit_code == 0, result.output
    assert len(buildx_for(fake, "Dockerfile.nginx")) == 1
    assert not buildx_for(fake, "Dockerfile.php")
    assert not fake.commands_starting("docker", "image", "inspect")


def test_build_on_a_deployment_branch_tags_branch_and_build_only(repo):
    fake = FakeRunner(*PRODUCTION, BASE_PRESENT)

    result = invoke(fake, repo, "build")

    assert result.exit_code == 0, result.output
    (app,) = buildx_for(fake, "Dockerfile.php")
    tags = [app[i + 1] for i, a in enumerate(app) if a == "-t"]
    assert tags == [f"{APP}:production", f"{APP}:production-v1.6.0-0123456"]
    assert "APP_VERSION=1.6.0" in app


def test_build_on_main_uses_the_version_about_to_be_bumped(repo):
    fake = FakeRunner(
        GIT_SHA,
        ON_MAIN,
        BUMP_TO_130,
        answer(("sh", VERSION_SCRIPT, "main", "1.3.0"), 0, "1.3.0\n"),
        BASE_PRESENT,
    )

    result = invoke(fake, repo, "build")

    assert result.exit_code == 0, result.output
    (app,) = buildx_for(fake, "Dockerfile.php")
    assert f"{APP}:v1.3.0" in app
    assert "APP_VERSION=1.3.0" in app
    assert not fake.commands_starting(*CZ, "bump", "--yes")


def test_build_with_registry_cache_logs_in_first(repo):
    fake = FakeRunner(*MAIN, BASE_PRESENT, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "build", "--registry-cache")

    assert result.exit_code == 0, result.output
    assert fake.index_of("docker", "login") < fake.index_of(
        "docker",
        "buildx",
        "build",
    )
    (app,) = buildx_for(fake, "Dockerfile.php")
    assert f"type=registry,ref={APP}:cache" in app


# -- login, push, bump, deploy ------------------------------------------------


def test_login_logs_docker_in_to_ecr(repo):
    fake = FakeRunner(AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "login")

    assert result.exit_code == 0, result.output
    assert fake.commands_starting("docker", "login")
    assert "755997884632" in result.output


def test_login_fails_cleanly_without_credentials(repo):
    fake = FakeRunner(AWS_LOGGED_OUT)

    result = CliRunner().invoke(main, ["--repo", str(repo), "login"], obj=fake)

    assert result.exit_code != 0
    assert "Traceback" not in result.output
    assert "aws login" in result.output


def test_push_logs_in_and_pushes_every_main_tag(repo):
    fake = FakeRunner(*MAIN, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "push")

    assert result.exit_code == 0, result.output
    pushes = fake.commands_starting("docker", "push")
    assert len(pushes) == MAIN_PUSHES
    assert ["docker", "push", f"{APP}:v1.2.1"] in pushes
    assert ["docker", "push", f"{NGINX}:latest"] in pushes
    assert fake.index_of("docker", "login") < fake.index_of("docker", "push")
    assert not fake.commands_starting("aws", "ecr", "put-image")


def test_push_on_a_deployment_branch_pushes_branch_and_build_tags(repo):
    fake = FakeRunner(*PRODUCTION, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "push")

    assert result.exit_code == 0, result.output
    pushes = fake.commands_starting("docker", "push")
    assert len(pushes) == BRANCH_PUSHES
    assert ["docker", "push", f"{APP}:production"] in pushes
    assert ["docker", "push", f"{APP}:production-v1.6.0-0123456"] in pushes


def test_push_can_skip_the_login_and_include_the_base(repo):
    fake = FakeRunner(*MAIN)

    result = invoke(fake, repo, "push", "--skip-login", "--with-base")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting("aws")
    assert fake.commands_starting("docker", "push")[0] == [
        "docker",
        "push",
        BASE,
    ]


def test_push_can_label_the_registry_cache_manifests(repo):
    fake = FakeRunner(
        *MAIN,
        AWS_OK,
        ECR_TOKEN,
        answer(("aws", "ecr", "batch-get-image"), 0, "m\n"),
    )

    result = invoke(fake, repo, "push", "--label-cache")

    assert result.exit_code == 0, result.output
    puts = fake.commands_starting("aws", "ecr", "put-image")
    repos = {p[p.index("--repository-name") + 1] for p in puts}
    assert repos == {"commons-wordpress-nginx", "commons-wordpress-app"}
    assert fake.index_of("docker", "push") < fake.index_of(
        "aws",
        "ecr",
        "put-image",
    )


def test_push_can_be_limited_to_one_image(repo):
    fake = FakeRunner(*MAIN, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "push", "--image", "app")

    assert result.exit_code == 0, result.output
    pushes = fake.commands_starting("docker", "push")
    assert len(pushes) == MAIN_PUSHES // 2
    assert all(APP in p[2] for p in pushes)


def test_bump_runs_commitizen(repo):
    fake = FakeRunner(GIT_SHA)

    result = invoke(fake, repo, "bump")

    assert result.exit_code == 0, result.output
    assert [*CZ, "bump", "--yes"] in fake.commands
    assert not fake.commands_starting("git", "push")


def test_bump_reports_when_there_is_nothing_to_bump(repo):
    fake = FakeRunner(GIT_SHA, answer((*CZ, "bump", "--yes"), 21, ""))

    result = invoke(fake, repo, "bump")

    assert result.exit_code == 0, result.output
    assert "nothing" in result.output.lower()


def test_deploy_requires_a_cluster_and_service(repo):
    result = CliRunner().invoke(
        main,
        ["--repo", str(repo), "deploy"],
        obj=FakeRunner(),
    )

    assert result.exit_code != 0
    assert "--cluster" in result.output or "--service" in result.output


def test_deploy_forces_a_new_ecs_deployment(repo):
    fake = FakeRunner(AWS_OK)

    result = invoke(
        fake,
        repo,
        "deploy",
        "--cluster",
        "wordpress-prod",
        "--service",
        "wp",
    )

    assert result.exit_code == 0, result.output
    (update,) = fake.commands_starting("aws", "ecs", "update-service")
    assert "--force-new-deployment" in update


# -- release ----------------------------------------------------------------


def test_release_on_main_tests_builds_pushes_then_bumps(repo):
    fake = FakeRunner(
        GIT_SHA,
        ON_MAIN,
        BUMP_TO_130,
        answer(("sh", VERSION_SCRIPT, "main", "1.3.0"), 0, "1.3.0\n"),
        BASE_PRESENT,
        AWS_OK,
        ECR_TOKEN,
    )

    result = invoke(fake, repo, "release")

    assert result.exit_code == 0, result.output
    tests = fake.index_of("vendor/bin/phpunit")
    build = fake.index_of("docker", "buildx", "build")
    push = fake.index_of("docker", "push")
    bump = fake.index_of(*CZ, "bump", "--yes")
    assert tests < build < push < bump
    assert len(fake.commands_starting("docker", "push")) == MAIN_PUSHES
    assert ["docker", "push", f"{APP}:v1.3.0"] in fake.commands
    assert not fake.commands_starting("git", "push")


def test_release_pushes_the_bump_commit_when_asked(repo):
    fake = FakeRunner(
        GIT_SHA,
        ON_MAIN,
        BUMP_TO_130,
        answer(("sh", VERSION_SCRIPT, "main", "1.3.0"), 0, "1.3.0\n"),
        BASE_PRESENT,
        AWS_OK,
        ECR_TOKEN,
    )

    result = invoke(fake, repo, "release", "--push-git")

    assert result.exit_code == 0, result.output
    assert ["git", "push", "origin", "main", "--tags"] in fake.commands
    assert fake.index_of(*CZ, "bump", "--yes") < fake.index_of("git", "push")


def test_release_on_main_without_semantic_commits_does_not_bump(repo):
    fake = FakeRunner(*MAIN, BASE_PRESENT, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "release", "--push-git")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting(*CZ, "bump", "--yes")
    assert not fake.commands_starting("git", "push")
    assert ["docker", "push", f"{APP}:v1.2.1"] in fake.commands


def test_release_on_a_deployment_branch_never_bumps(repo):
    fake = FakeRunner(*PRODUCTION, BASE_PRESENT, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "release", "--push-git")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting("uvx")
    assert not fake.commands_starting("git", "push")
    assert len(fake.commands_starting("docker", "push")) == BRANCH_PUSHES


def test_release_without_push_does_not_bump_either(repo):
    fake = FakeRunner(
        GIT_SHA,
        ON_MAIN,
        BUMP_TO_130,
        answer(("sh", VERSION_SCRIPT, "main", "1.3.0"), 0, "1.3.0\n"),
        BASE_PRESENT,
    )

    result = invoke(fake, repo, "release", "--skip-push")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting("docker", "push")
    assert not fake.commands_starting(*CZ, "bump", "--yes")
    assert not fake.commands_starting("aws")


def test_release_can_skip_tests(repo):
    fake = FakeRunner(*MAIN, BASE_PRESENT, AWS_OK, ECR_TOKEN)

    result = invoke(fake, repo, "release", "--skip-tests")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting("vendor/bin/phpunit")


def test_release_stops_before_building_when_tests_fail(repo):
    fake = FakeRunner(*MAIN, answer(("vendor/bin/phpunit",), 2, ""))

    result = CliRunner().invoke(
        main,
        ["--repo", str(repo), "release"],
        obj=fake,
    )

    assert result.exit_code == 2
    assert not fake.commands_starting("docker", "buildx")


# -- local stack --------------------------------------------------------------


def test_up_builds_starts_and_configures_the_stack(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "up")

    assert result.exit_code == 0, result.output
    build = fake.index_of(*COMPOSE, "build")
    up = fake.index_of(*COMPOSE, "up", "-d")
    setup = fake.index_of(*COMPOSE, "run", "--rm", "setup")
    bp = fake.index_of(*COMPOSE, "exec", "app", "bash")
    assert build < up < setup < bp
    assert "hcommons.test" in result.output


def test_up_can_skip_the_build_and_the_setup(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "up", "--no-build", "--no-setup")

    assert result.exit_code == 0, result.output
    assert fake.commands == [[*COMPOSE, "up", "-d", "db", "app", "nginx"]]


def test_override_files_reach_every_compose_command(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "-f", "local.yml", "up", "--no-setup")

    assert result.exit_code == 0, result.output
    for command in fake.commands_starting("docker", "compose"):
        assert command[: len(COMPOSE) + 2] == [*COMPOSE, "-f", "local.yml"]


def test_down_stops_the_stack_and_can_drop_volumes(repo):
    fake = FakeRunner()

    assert invoke(fake, repo, "down").exit_code == 0
    assert invoke(fake, repo, "down", "--volumes").exit_code == 0
    assert fake.commands == [
        [*COMPOSE, "down", "--remove-orphans"],
        [*COMPOSE, "down", "-v", "--remove-orphans"],
    ]


def test_logs_follow_the_requested_services(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "logs", "app", "nginx")

    assert result.exit_code == 0, result.output
    assert fake.commands == [[*COMPOSE, "logs", "-f", "app", "nginx"]]


def test_e2e_runs_the_full_sequence_and_tears_down(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "e2e")

    assert result.exit_code == 0, result.output
    assert fake.index_of(*COMPOSE, "build") < fake.index_of(*COMPOSE, "up")
    assert fake.index_of(*COMPOSE, "run", "--rm", "setup") < fake.index_of(
        *COMPOSE,
        "run",
        "--rm",
        "playwright",
    )
    assert fake.lines[-1] == " ".join(
        [*COMPOSE, "down", "-v", "--remove-orphans"]
    )


def test_e2e_propagates_the_playwright_status_and_dumps_php_logs(repo):
    fake = FakeRunner(answer((*COMPOSE, "run", "--rm", "playwright"), 1, ""))

    result = CliRunner().invoke(main, ["--repo", str(repo), "e2e"], obj=fake)

    assert result.exit_code == 1
    assert fake.commands_starting(*COMPOSE, "exec", "app", "cat")
    assert fake.commands_starting(*COMPOSE, "down")


def test_e2e_can_keep_the_stack_up_and_reuse_it(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "e2e", "--keep-up", "--no-build", "--no-setup")

    assert result.exit_code == 0, result.output
    assert not fake.commands_starting(*COMPOSE, "build")
    assert not fake.commands_starting(*COMPOSE, "run", "--rm", "setup")
    assert not fake.commands_starting(*COMPOSE, "down")
    assert fake.commands_starting(*COMPOSE, "run", "--rm", "playwright")


def test_wp_passes_arguments_through_to_wp_cli(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "wp", "plugin", "list", "--status=active")

    assert result.exit_code == 0, result.output
    assert fake.commands == [
        [
            *COMPOSE,
            "exec",
            "app",
            "wp",
            "--allow-root",
            "plugin",
            "list",
            "--status=active",
        ]
    ]


def test_shell_opens_bash_in_the_app_container_by_default(repo):
    fake = FakeRunner()

    result = invoke(fake, repo, "shell")
    assert result.exit_code == 0, result.output
    assert fake.commands == [[*COMPOSE, "exec", "app", "bash"]]

    result = invoke(fake, repo, "shell", "--service", "nginx")
    assert fake.commands[-1] == [*COMPOSE, "exec", "nginx", "sh"]


def test_dry_run_is_accepted_and_prints_commands(repo, monkeypatch):
    result = CliRunner().invoke(
        main,
        ["--repo", str(repo), "--dry-run", "lint"],
    )

    assert result.exit_code == 0, result.output
    assert "$ composer test" in result.output
