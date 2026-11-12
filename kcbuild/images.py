"""Docker build and push operations.

Each image is built with one ``docker buildx build`` carrying every tag
and build argument the ``docker/build-push-action`` step passes in CI.
Provenance and SBOM attestations are disabled for the same reason CI
disables them: ECR shows them as untagged images.
"""

from pathlib import Path

from kcbuild.config import APP_DOCKERFILE
from kcbuild.config import BASE_REPOSITORY
from kcbuild.config import Image
from kcbuild.metadata import BuildInfo
from kcbuild.metadata import ImageTags
from kcbuild.metadata import app_env
from kcbuild.runner import Runner

BUILDX = ["docker", "buildx", "build", "--provenance=false", "--sbom=false"]


def image_ref(registry: str, repository: str, tag: str) -> str:
    return f"{registry}/{repository}:{tag}"


def base_image_ref(registry: str) -> str:
    return image_ref(registry, BASE_REPOSITORY, "latest")


def cache_ref(registry: str, repository: str) -> str:
    return image_ref(registry, repository, "cache")


def image_exists(runner: Runner, ref: str) -> bool:
    result = runner.run(
        ["docker", "image", "inspect", ref],
        capture=True,
        check=False,
        always_run=True,
    )
    return result.returncode == 0


def pull_image(runner: Runner, ref: str) -> None:
    runner.run(["docker", "pull", ref])


def build_args(
    image: Image,
    registry: str,
    info: BuildInfo,
    tags: ImageTags,
) -> dict[str, str]:
    args: dict[str, str] = {}
    if image.uses_base:
        args["BASE_IMAGE"] = base_image_ref(registry)
    args.update(
        {
            "APP_VERSION": info.version,
            "BUILD_TAG": tags.build_tag,
            "GIT_SHA": info.sha,
            "APP_BRANCH": info.branch,
        }
    )
    if image.passes_app_env:
        args["APP_ENV"] = app_env(info.branch)
    return args


def image_refs(image: Image, registry: str, tags: ImageTags) -> list[str]:
    return [image_ref(registry, image.repository, tag) for tag in tags.all]


def _cache_flags(registry: str, repository: str) -> list[str]:
    ref = cache_ref(registry, repository)
    return [
        "--cache-from",
        f"type=registry,ref={ref}",
        "--cache-to",
        f"type=registry,ref={ref},mode=max",
    ]


def build_image(  # noqa: PLR0917
    runner: Runner,
    repo: Path,
    image: Image,
    registry: str,
    info: BuildInfo,
    tags: ImageTags,
    *,
    no_cache: bool = False,
    registry_cache: bool = False,
) -> list[str]:
    refs = image_refs(image, registry, tags)
    command = [*BUILDX, "-f", image.dockerfile]
    if image.target:
        command += ["--target", image.target]
    for key, value in build_args(image, registry, info, tags).items():
        command += ["--build-arg", f"{key}={value}"]
    for ref in refs:
        command += ["-t", ref]
    if registry_cache:
        command += _cache_flags(registry, image.repository)
    if no_cache:
        command.append("--no-cache")
    command += ["--load", "."]
    runner.run(command, cwd=repo)
    return refs


def build_base(
    runner: Runner,
    repo: Path,
    registry: str,
    *,
    no_cache: bool = False,
    registry_cache: bool = False,
) -> str:
    ref = base_image_ref(registry)
    command = [*BUILDX, "-f", APP_DOCKERFILE, "--target", "base", "-t", ref]
    if registry_cache:
        command += _cache_flags(registry, BASE_REPOSITORY)
    if no_cache:
        command.append("--no-cache")
    command += ["--load", "."]
    runner.run(command, cwd=repo)
    return ref


def push_refs(
    images: list[Image],
    registry: str,
    tags: ImageTags,
) -> list[str]:
    return [
        ref for image in images for ref in image_refs(image, registry, tags)
    ]


def push_images(runner: Runner, refs: list[str]) -> None:
    for ref in refs:
        runner.run(["docker", "push", ref])
