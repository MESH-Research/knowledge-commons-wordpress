"""Drive the local ``docker-compose.test.yml`` stack.

These are the phases of ``scripts/test-scripts/run-e2e.sh``, exposed one
at a time so the stack can be brought up and left running for manual work
as well as torn down after a Playwright run. Every compose invocation
carries the same ``-f`` files, because ``docker compose run`` recreates
dependent services and would otherwise drop bind-mount overrides.
"""

from pathlib import Path

from kcbuild.config import TEST_COMPOSE_FILE
from kcbuild.runner import Runner

STACK_SERVICES = ("db", "app", "nginx")
SETUP_BP_SCRIPT = "/app/scripts/test-scripts/setup-bp.sh"
PHP_FATAL_LOG = "/tmp/php-fatal.log"
ADMIN_URL = "http://nginx:80/wp/wp-admin/"
TEST_HOST = "hcommons.test"
FPM_WARMUP_SECONDS = "5"
BP_INIT_SETTLE_SECONDS = "3"


def compose_command(overrides: list[str] | None = None) -> list[str]:
    command = ["docker", "compose", "-f", TEST_COMPOSE_FILE]
    for override in overrides or []:
        command += ["-f", override]
    return command


def _compose(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None,
    args: list[str],
    *,
    check: bool = True,
) -> int:
    result = runner.run(
        [*compose_command(overrides), *args],
        cwd=repo,
        check=check,
    )
    return result.returncode


def stack_build(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
    services: list[str] | None = None,
) -> None:
    _compose(runner, repo, overrides, ["build", *(services or [])])


def stack_up(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
    *,
    build: bool = True,
) -> None:
    if build:
        stack_build(runner, repo, overrides)
    _compose(runner, repo, overrides, ["up", "-d", *STACK_SERVICES])


def _admin_request(runner: Runner, repo: Path, overrides: list[str] | None):
    # BuddyPress initialises itself on the first web request through
    # PHP-FPM and overwrites CLI-set options when it does, so it must run
    # before setup-bp.sh configures it.
    _compose(
        runner,
        repo,
        overrides,
        [
            "exec",
            "app",
            "curl",
            "-s",
            "-o",
            "/dev/null",
            "-H",
            f"Host: {TEST_HOST}",
            "-H",
            "Cookie: wordpress_logged_in_dummy=1",
            ADMIN_URL,
        ],
        check=False,
    )


def stack_setup(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
) -> None:
    """Install WordPress, trigger BuddyPress init, then configure BP."""
    runner.run(["sleep", FPM_WARMUP_SECONDS])
    _compose(runner, repo, overrides, ["run", "--rm", "setup"])
    _admin_request(runner, repo, overrides)
    runner.run(["sleep", BP_INIT_SETTLE_SECONDS])
    _admin_request(runner, repo, overrides)
    runner.run(["sleep", "1"])
    _compose(
        runner,
        repo,
        overrides,
        ["exec", "app", "bash", SETUP_BP_SCRIPT],
    )


def stack_e2e(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
) -> int:
    return _compose(
        runner,
        repo,
        overrides,
        ["run", "--rm", "playwright"],
        check=False,
    )


def dump_php_logs(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
) -> None:
    _compose(
        runner,
        repo,
        overrides,
        ["exec", "app", "cat", PHP_FATAL_LOG],
        check=False,
    )
    _compose(
        runner,
        repo,
        overrides,
        ["logs", "--tail=50", "app"],
        check=False,
    )


def stack_down(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
    *,
    volumes: bool = False,
) -> None:
    args = ["down", "-v"] if volumes else ["down"]
    _compose(runner, repo, overrides, [*args, "--remove-orphans"])


def stack_logs(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None = None,
    services: list[str] | None = None,
    *,
    follow: bool = True,
) -> None:
    args = ["logs", "-f"] if follow else ["logs"]
    _compose(runner, repo, overrides, [*args, *(services or [])])


def stack_exec(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None,
    service: str,
    args: list[str],
    *,
    check: bool = True,
) -> int:
    return _compose(
        runner,
        repo,
        overrides,
        ["exec", service, *args],
        check=check,
    )


def stack_wp(
    runner: Runner,
    repo: Path,
    overrides: list[str] | None,
    args: list[str],
) -> int:
    return stack_exec(
        runner,
        repo,
        overrides,
        "app",
        ["wp", "--allow-root", *args],
        check=False,
    )
