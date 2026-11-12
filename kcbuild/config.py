"""Constants mirrored from ``.github/workflows/push-to-aws.yml`` and
``build-base-image.yml``.

Keep these in step with the workflows: the point of this tool is to
produce images that are indistinguishable from a CI build.
"""

from dataclasses import dataclass

AWS_ACCOUNT_ID = "755997884632"
AWS_REGION = "us-east-1"
COMMITIZEN_VERSION = "4.16.2"

APP_DOCKERFILE = "Dockerfile.php"
NGINX_DOCKERFILE = "Dockerfile.nginx"
VERSION_FILE = "VERSION"
RESOLVE_VERSION_SCRIPT = "scripts/build-scripts/resolve-build-version.sh"
TEST_COMPOSE_FILE = "docker-compose.test.yml"

BASE_REPOSITORY = "commons-wordpress-base"
APP_REPOSITORY = "commons-wordpress-app"
NGINX_REPOSITORY = "commons-wordpress-nginx"

#: Branches the push workflow builds for. Only ``main`` owns the version
#: line and receives ``:v<version>`` and ``:latest`` tags.
DEPLOY_BRANCHES = ("main", "production", "dev", "staging", "test")
RELEASE_BRANCH = "main"


@dataclass(frozen=True)
class Image:
    """One of the two application images the push workflow builds."""

    name: str
    repository: str
    dockerfile: str
    target: str | None
    uses_base: bool
    passes_app_env: bool


IMAGES: dict[str, Image] = {
    "nginx": Image(
        name="nginx",
        repository=NGINX_REPOSITORY,
        dockerfile=NGINX_DOCKERFILE,
        target=None,
        uses_base=False,
        passes_app_env=True,
    ),
    "app": Image(
        name="app",
        repository=APP_REPOSITORY,
        dockerfile=APP_DOCKERFILE,
        target="cloud",
        uses_base=True,
        passes_app_env=False,
    ),
}


def ecr_registry(
    account_id: str = AWS_ACCOUNT_ID,
    region: str = AWS_REGION,
) -> str:
    return f"{account_id}.dkr.ecr.{region}.amazonaws.com"


def is_release_branch(branch: str) -> bool:
    """Only ``main`` is bumped and receives version/latest tags."""
    return branch == RELEASE_BRANCH


def get_images(names: list[str] | None = None) -> list[Image]:
    """The images to build, in the order the workflow jobs are declared."""
    if not names:
        return list(IMAGES.values())
    unknown = [name for name in names if name not in IMAGES]
    if unknown:
        msg = (
            f"unknown image(s) {', '.join(unknown)}; "
            f"choose from {', '.join(IMAGES)}"
        )
        raise ValueError(msg)
    return [IMAGES[name] for name in IMAGES if name in names]
