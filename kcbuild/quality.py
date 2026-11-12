"""Version bump, lint and test stages.

The bump mirrors the workflow exactly: ``cz bump --get-next`` decides
before the build whether ``main`` is about to release, and ``cz bump
--yes`` runs only after every image has been pushed.
"""

from pathlib import Path

from kcbuild.config import COMMITIZEN_VERSION
from kcbuild.runner import CommandError
from kcbuild.runner import Runner

#: commitizen exit codes meaning "nothing to release": NO_COMMITS_FOUND and
#: NO_INCREMENT (only chore:/docs: commits since the last tag).
CZ_NOTHING_TO_BUMP_EXIT_CODES = frozenset({3, 21})

PHPUNIT = "vendor/bin/phpunit"
PHPUNIT_CONFIGS: dict[str, str] = {
    "site": "tests/unit/phpunit.xml",
    "ci-logon": "mu-plugins/ci-logon/tests/phpunit.xml",
}
NGINX_TEST_SCRIPT = "tests/nginx/test-members-redirects.sh"


def commitizen_command() -> list[str]:
    return ["uvx", "--from", f"commitizen=={COMMITIZEN_VERSION}", "cz"]


def compute_next_version(
    runner: Runner,
    repo: Path,
    current: str,
) -> str | None:
    """The version commitizen would bump to, or None when it would not.

    Exit code 21 (no semantic commits) and an unchanged version both mean
    "no release", exactly as the ``prepare-version`` job treats them.
    """
    result = runner.run(
        [*commitizen_command(), "bump", "--get-next"],
        cwd=repo,
        capture=True,
        check=False,
        always_run=True,
    )
    next_version = result.stdout.strip().splitlines()[-1:] or [""]
    candidate = next_version[0].strip()
    if result.returncode != 0 or not candidate or candidate == current:
        return None
    return candidate


def bump_version(runner: Runner, repo: Path) -> bool:
    """Run ``cz bump --yes``; True when a bump commit was created."""
    result = runner.run(
        [*commitizen_command(), "bump", "--yes"],
        cwd=repo,
        check=False,
    )
    if result.returncode in CZ_NOTHING_TO_BUMP_EXIT_CODES:
        return False
    if result.returncode != 0:
        raise CommandError(result)
    return True


def push_git(runner: Runner, repo: Path, branch: str) -> None:
    runner.run(["git", "push", "origin", branch, "--tags"], cwd=repo)


def run_lint(runner: Runner, repo: Path) -> None:
    """``composer test`` is the repository's phpcs entry point."""
    runner.run(["composer", "test"], cwd=repo)


def run_unit_tests(
    runner: Runner,
    repo: Path,
    suite: str,
    *,
    filter_pattern: str | None = None,
) -> None:
    if suite not in PHPUNIT_CONFIGS:
        msg = (
            f"unknown suite {suite!r}; choose from {', '.join(PHPUNIT_CONFIGS)}"
        )
        raise ValueError(msg)
    command = [PHPUNIT, f"--configuration={PHPUNIT_CONFIGS[suite]}"]
    if filter_pattern:
        command += ["--filter", filter_pattern]
    runner.run(command, cwd=repo)


def run_nginx_tests(
    runner: Runner,
    repo: Path,
    environments: list[str] | None = None,
) -> None:
    runner.run(["bash", NGINX_TEST_SCRIPT, *(environments or [])], cwd=repo)
