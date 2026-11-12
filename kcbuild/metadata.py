"""Git and version metadata that feed the image tags.

Mirrors the ``prepare-version`` job of ``push-to-aws.yml``: the version
comes from ``scripts/build-scripts/resolve-build-version.sh`` (the same
script CI runs), the build tag is ``v<version>-<sha7>`` on ``main`` and
``<branch>-v<version>-<sha7>`` elsewhere, and only ``main`` is tagged with
``v<version>`` and ``latest``.
"""

from dataclasses import dataclass
from pathlib import Path

from kcbuild.config import RESOLVE_VERSION_SCRIPT
from kcbuild.config import VERSION_FILE
from kcbuild.config import is_release_branch
from kcbuild.runner import Runner

SHORT_SHA_LENGTH = 7


@dataclass(frozen=True)
class BuildInfo:
    sha: str
    branch: str
    version: str
    bumped: bool = False

    @property
    def short_sha(self) -> str:
        return self.sha[:SHORT_SHA_LENGTH]


@dataclass(frozen=True)
class ImageTags:
    """The tags the push workflow applies to every image.

    ``branch_tag`` moves with each build of a branch, ``build_tag`` is the
    unique ``[branch-]vX.Y.Z-<sha7>`` rollback name, and ``version_tag``
    plus ``latest`` exist only for ``main``.
    """

    branch_tag: str
    build_tag: str
    version_tag: str | None
    latest: bool

    @property
    def all(self) -> list[str]:
        tags = [self.branch_tag, self.build_tag]
        if self.version_tag:
            tags.append(self.version_tag)
        if self.latest:
            tags.append("latest")
        return tags


def read_version_file(repo: Path) -> str:
    return (repo / VERSION_FILE).read_text().strip()


def git_sha(runner: Runner, repo: Path) -> str:
    result = runner.run(
        ["git", "rev-parse", "HEAD"],
        cwd=repo,
        capture=True,
        always_run=True,
    )
    return result.stdout.strip()


def git_branch(runner: Runner, repo: Path) -> str:
    result = runner.run(
        ["git", "rev-parse", "--abbrev-ref", "HEAD"],
        cwd=repo,
        capture=True,
        always_run=True,
    )
    return result.stdout.strip()


def resolve_version(
    runner: Runner,
    repo: Path,
    branch: str,
    next_version: str | None = None,
) -> str:
    """Ask the workflow's own script which version this build carries."""
    args = ["sh", RESOLVE_VERSION_SCRIPT, branch]
    if next_version:
        args.append(next_version)
    result = runner.run(args, cwd=repo, capture=True, always_run=True)
    return result.stdout.strip()


def compute_build_tag(branch: str, version: str, short_sha: str) -> str:
    tag = f"v{version}-{short_sha}"
    if is_release_branch(branch):
        return tag
    return f"{branch}-{tag}"


def app_env(branch: str) -> str:
    """The nginx template overlay to bake in: the branch name, verbatim."""
    return branch


def compute_tags(info: BuildInfo) -> ImageTags:
    release = is_release_branch(info.branch)
    return ImageTags(
        branch_tag=info.branch,
        build_tag=compute_build_tag(info.branch, info.version, info.short_sha),
        version_tag=f"v{info.version}" if release else None,
        latest=release,
    )


def collect_build_info(
    runner: Runner,
    repo: Path,
    *,
    branch: str | None = None,
    next_version: str | None = None,
) -> BuildInfo:
    branch = branch or git_branch(runner, repo)
    return BuildInfo(
        sha=git_sha(runner, repo),
        branch=branch,
        version=resolve_version(runner, repo, branch, next_version),
        bumped=next_version is not None,
    )
