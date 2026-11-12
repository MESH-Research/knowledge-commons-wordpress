import pytest

from kcbuild import metadata
from tests.kcbuild.support import FakeRunner
from tests.kcbuild.support import answer

SHA = "0123456789abcdef0123456789abcdef01234567"
GIT = (
    answer(("git", "rev-parse", "HEAD"), 0, f"{SHA}\n"),
    answer(("git", "rev-parse", "--abbrev-ref", "HEAD"), 0, "production\n"),
)
VERSION_SCRIPT = "scripts/build-scripts/resolve-build-version.sh"


def info(branch: str, version: str = "1.2.1", bumped: bool = False):
    return metadata.BuildInfo(
        sha=SHA,
        branch=branch,
        version=version,
        bumped=bumped,
    )


def test_short_sha_is_seven_characters():
    assert info("main").short_sha == "0123456"


def test_version_file_is_read_without_surrounding_whitespace(tmp_path):
    (tmp_path / "VERSION").write_text("1.2.1\n")

    assert metadata.read_version_file(tmp_path) == "1.2.1"


def test_git_sha_and_branch_come_from_git(tmp_path):
    fake = FakeRunner(*GIT)

    assert metadata.git_sha(fake, tmp_path) == SHA
    assert metadata.git_branch(fake, tmp_path) == "production"


def test_version_is_resolved_by_the_workflow_script(tmp_path):
    fake = FakeRunner(answer(("sh", VERSION_SCRIPT), 0, "1.6.0\n"))

    version = metadata.resolve_version(fake, tmp_path, "production")

    assert version == "1.6.0"
    assert fake.commands == [["sh", VERSION_SCRIPT, "production"]]


def test_next_version_is_passed_through_to_the_script(tmp_path):
    fake = FakeRunner(answer(("sh", VERSION_SCRIPT), 0, "1.7.0\n"))

    version = metadata.resolve_version(fake, tmp_path, "main", "1.7.0")

    assert version == "1.7.0"
    assert fake.commands == [["sh", VERSION_SCRIPT, "main", "1.7.0"]]


def test_main_build_tag_is_version_and_sha():
    assert metadata.compute_build_tag("main", "1.2.1", "0123456") == (
        "v1.2.1-0123456"
    )


@pytest.mark.parametrize("branch", ["production", "dev", "staging", "test"])
def test_other_branches_prefix_the_build_tag(branch):
    assert metadata.compute_build_tag(branch, "1.2.1", "0123456") == (
        f"{branch}-v1.2.1-0123456"
    )


@pytest.mark.parametrize("branch", ["production", "dev", "main", "feature/x"])
def test_app_env_is_the_branch_name(branch):
    assert metadata.app_env(branch) == branch


def test_main_gets_branch_build_version_and_latest_tags():
    tags = metadata.compute_tags(info("main"))

    assert tags.branch_tag == "main"
    assert tags.build_tag == "v1.2.1-0123456"
    assert tags.version_tag == "v1.2.1"
    assert tags.latest is True
    assert tags.all == ["main", "v1.2.1-0123456", "v1.2.1", "latest"]


def test_other_branches_get_only_branch_and_build_tags():
    tags = metadata.compute_tags(info("production", "1.6.0"))

    assert tags.version_tag is None
    assert tags.latest is False
    assert tags.all == ["production", "production-v1.6.0-0123456"]


def test_build_info_uses_the_checked_out_branch_by_default(tmp_path):
    fake = FakeRunner(*GIT, answer(("sh", VERSION_SCRIPT), 0, "1.6.0\n"))

    built = metadata.collect_build_info(fake, tmp_path)

    assert built == metadata.BuildInfo(
        sha=SHA,
        branch="production",
        version="1.6.0",
    )
    assert ["sh", VERSION_SCRIPT, "production"] in fake.commands


def test_build_info_branch_can_be_overridden(tmp_path):
    fake = FakeRunner(*GIT, answer(("sh", VERSION_SCRIPT), 0, "1.2.1\n"))

    built = metadata.collect_build_info(fake, tmp_path, branch="main")

    assert built.branch == "main"
    assert ["sh", VERSION_SCRIPT, "main"] in fake.commands
    assert not any(
        args[:3] == ["git", "rev-parse", "--abbrev-ref"]
        for args in fake.commands
    )


def test_build_info_records_a_pending_bump(tmp_path):
    fake = FakeRunner(*GIT, answer(("sh", VERSION_SCRIPT), 0, "1.3.0\n"))

    built = metadata.collect_build_info(
        fake,
        tmp_path,
        branch="main",
        next_version="1.3.0",
    )

    assert built.version == "1.3.0"
    assert built.bumped is True
    assert ["sh", VERSION_SCRIPT, "main", "1.3.0"] in fake.commands
