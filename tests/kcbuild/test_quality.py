import pytest

from kcbuild import quality
from kcbuild.runner import CommandError
from tests.kcbuild.support import FakeRunner
from tests.kcbuild.support import answer

CZ = ("uvx", "--from", "commitizen==4.16.2", "cz")


def test_commitizen_is_pinned_to_the_workflow_version():
    assert quality.commitizen_command() == list(CZ)


def test_next_version_is_reported_when_commitizen_would_bump(tmp_path):
    fake = FakeRunner(answer((*CZ, "bump", "--get-next"), 0, "1.3.0\n"))

    assert quality.compute_next_version(fake, tmp_path, "1.2.1") == "1.3.0"


def test_no_bump_when_the_next_version_equals_the_current_one(tmp_path):
    fake = FakeRunner(answer((*CZ, "bump", "--get-next"), 0, "1.2.1\n"))

    assert quality.compute_next_version(fake, tmp_path, "1.2.1") is None


@pytest.mark.parametrize("exit_code", [3, 21])
def test_no_bump_when_commitizen_finds_no_semantic_commits(
    tmp_path,
    exit_code,
):
    fake = FakeRunner(answer((*CZ, "bump", "--get-next"), exit_code, ""))

    assert quality.compute_next_version(fake, tmp_path, "1.2.1") is None


def test_no_bump_when_commitizen_prints_nothing(tmp_path):
    fake = FakeRunner(answer((*CZ, "bump", "--get-next"), 0, ""))

    assert quality.compute_next_version(fake, tmp_path, "1.2.1") is None


def test_bump_version_reports_a_new_commit(tmp_path):
    fake = FakeRunner()

    assert quality.bump_version(fake, tmp_path) is True
    assert [*CZ, "bump", "--yes"] in fake.commands


@pytest.mark.parametrize("exit_code", [3, 21])
def test_bump_version_is_false_when_nothing_to_bump(tmp_path, exit_code):
    fake = FakeRunner(answer((*CZ, "bump", "--yes"), exit_code, ""))

    assert quality.bump_version(fake, tmp_path) is False


def test_bump_version_raises_on_other_failures(tmp_path):
    fake = FakeRunner(answer((*CZ, "bump", "--yes"), 1, ""))

    with pytest.raises(CommandError):
        quality.bump_version(fake, tmp_path)


def test_push_git_pushes_the_branch_with_tags(tmp_path):
    fake = FakeRunner()

    quality.push_git(fake, tmp_path, "main")

    assert fake.commands == [["git", "push", "origin", "main", "--tags"]]


def test_lint_runs_the_composer_test_script(tmp_path):
    fake = FakeRunner()

    quality.run_lint(fake, tmp_path)

    assert fake.commands == [["composer", "test"]]


@pytest.mark.parametrize(
    ("suite", "config"),
    [
        ("site", "tests/unit/phpunit.xml"),
        ("ci-logon", "mu-plugins/ci-logon/tests/phpunit.xml"),
    ],
)
def test_unit_tests_use_the_vendored_phpunit_and_suite_config(
    tmp_path,
    suite,
    config,
):
    fake = FakeRunner()

    quality.run_unit_tests(fake, tmp_path, suite)

    assert fake.commands == [
        ["vendor/bin/phpunit", f"--configuration={config}"],
    ]


def test_unit_tests_can_be_filtered(tmp_path):
    fake = FakeRunner()

    quality.run_unit_tests(fake, tmp_path, "site", filter_pattern="Forum")

    (command,) = fake.commands
    assert command[-2:] == ["--filter", "Forum"]


def test_unknown_suite_is_rejected(tmp_path):
    with pytest.raises(ValueError, match="e2e"):
        quality.run_unit_tests(FakeRunner(), tmp_path, "e2e")


def test_nginx_tests_run_the_redirect_script(tmp_path):
    fake = FakeRunner()

    quality.run_nginx_tests(fake, tmp_path)

    assert fake.commands == [["bash", "tests/nginx/test-members-redirects.sh"]]


def test_nginx_tests_can_be_limited_to_environments(tmp_path):
    fake = FakeRunner()

    quality.run_nginx_tests(fake, tmp_path, ["production", "dev"])

    assert fake.commands[0][-2:] == ["production", "dev"]
