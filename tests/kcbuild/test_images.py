import pytest

from kcbuild import images
from kcbuild.config import get_images
from kcbuild.metadata import BuildInfo
from kcbuild.metadata import ImageTags
from tests.kcbuild.support import FakeRunner
from tests.kcbuild.support import answer

REG = "755997884632.dkr.ecr.us-east-1.amazonaws.com"
SHA = "0123456789abcdef0123456789abcdef01234567"
MAIN_INFO = BuildInfo(sha=SHA, branch="main", version="1.2.1")
MAIN_TAGS = ImageTags(
    branch_tag="main",
    build_tag="v1.2.1-0123456",
    version_tag="v1.2.1",
    latest=True,
)
PROD_INFO = BuildInfo(sha=SHA, branch="production", version="1.6.0")
PROD_TAGS = ImageTags(
    branch_tag="production",
    build_tag="production-v1.6.0-0123456",
    version_tag=None,
    latest=False,
)
(NGINX, APP) = get_images()


def pairs(command: list[str], flag: str) -> list[str]:
    """Values following every occurrence of ``flag`` in ``command``."""
    return [command[i + 1] for i, a in enumerate(command) if a == flag]


def test_image_and_base_references():
    assert images.image_ref(REG, "x", "t") == f"{REG}/x:t"
    assert images.base_image_ref(REG) == f"{REG}/commons-wordpress-base:latest"
    assert images.cache_ref(REG, "commons-wordpress-app") == (
        f"{REG}/commons-wordpress-app:cache"
    )


def test_image_exists_reflects_docker_inspect():
    present = FakeRunner(answer(("docker", "image", "inspect"), 0, "[]"))
    missing = FakeRunner(answer(("docker", "image", "inspect"), 1, ""))

    assert images.image_exists(present, "ref") is True
    assert images.image_exists(missing, "ref") is False


def test_pull_image_pulls_the_reference():
    fake = FakeRunner()

    images.pull_image(fake, f"{REG}/x:latest")

    assert fake.commands == [["docker", "pull", f"{REG}/x:latest"]]


def test_nginx_build_args_match_the_workflow():
    assert images.build_args(NGINX, REG, PROD_INFO, PROD_TAGS) == {
        "APP_VERSION": "1.6.0",
        "BUILD_TAG": "production-v1.6.0-0123456",
        "GIT_SHA": SHA,
        "APP_BRANCH": "production",
        "APP_ENV": "production",
    }


def test_app_build_args_match_the_workflow():
    assert images.build_args(APP, REG, MAIN_INFO, MAIN_TAGS) == {
        "BASE_IMAGE": f"{REG}/commons-wordpress-base:latest",
        "APP_VERSION": "1.2.1",
        "BUILD_TAG": "v1.2.1-0123456",
        "GIT_SHA": SHA,
        "APP_BRANCH": "main",
    }


def test_image_refs_cover_every_tag():
    assert images.image_refs(APP, REG, MAIN_TAGS) == [
        f"{REG}/commons-wordpress-app:main",
        f"{REG}/commons-wordpress-app:v1.2.1-0123456",
        f"{REG}/commons-wordpress-app:v1.2.1",
        f"{REG}/commons-wordpress-app:latest",
    ]
    assert images.image_refs(NGINX, REG, PROD_TAGS) == [
        f"{REG}/commons-wordpress-nginx:production",
        f"{REG}/commons-wordpress-nginx:production-v1.6.0-0123456",
    ]


def test_build_app_image_runs_one_buildx_build_with_all_tags(tmp_path):
    fake = FakeRunner()

    refs = images.build_image(fake, tmp_path, APP, REG, MAIN_INFO, MAIN_TAGS)

    (command,) = fake.commands
    assert command[:3] == ["docker", "buildx", "build"]
    assert command[-1] == "."
    assert pairs(command, "-f") == ["Dockerfile.php"]
    assert pairs(command, "--target") == ["cloud"]
    assert "--provenance=false" in command
    assert "--sbom=false" in command
    assert "--load" in command
    assert (
        set(pairs(command, "-t"))
        == set(refs)
        == set(
            images.image_refs(APP, REG, MAIN_TAGS),
        )
    )
    assert set(pairs(command, "--build-arg")) == {
        f"BASE_IMAGE={REG}/commons-wordpress-base:latest",
        "APP_VERSION=1.2.1",
        "BUILD_TAG=v1.2.1-0123456",
        f"GIT_SHA={SHA}",
        "APP_BRANCH=main",
    }
    assert "--no-cache" not in command
    assert not any(a.startswith("--cache") for a in command)


def test_build_nginx_image_has_no_target_and_passes_app_env(tmp_path):
    fake = FakeRunner()

    images.build_image(fake, tmp_path, NGINX, REG, PROD_INFO, PROD_TAGS)

    (command,) = fake.commands
    assert pairs(command, "-f") == ["Dockerfile.nginx"]
    assert "--target" not in command
    assert "APP_ENV=production" in pairs(command, "--build-arg")
    assert not any(
        a.startswith("BASE_IMAGE=") for a in pairs(command, "--build-arg")
    )


def test_build_image_can_disable_the_layer_cache(tmp_path):
    fake = FakeRunner()

    images.build_image(
        fake,
        tmp_path,
        APP,
        REG,
        MAIN_INFO,
        MAIN_TAGS,
        no_cache=True,
    )

    assert "--no-cache" in fake.commands[0]


def test_build_image_can_use_the_ecr_registry_cache(tmp_path):
    fake = FakeRunner()

    images.build_image(
        fake,
        tmp_path,
        APP,
        REG,
        MAIN_INFO,
        MAIN_TAGS,
        registry_cache=True,
    )

    (command,) = fake.commands
    assert pairs(command, "--cache-from") == [
        f"type=registry,ref={REG}/commons-wordpress-app:cache",
    ]
    assert pairs(command, "--cache-to") == [
        f"type=registry,ref={REG}/commons-wordpress-app:cache,mode=max",
    ]


def test_build_base_targets_the_base_stage(tmp_path):
    fake = FakeRunner()

    ref = images.build_base(fake, tmp_path, REG)

    assert ref == f"{REG}/commons-wordpress-base:latest"
    (command,) = fake.commands
    assert command[:3] == ["docker", "buildx", "build"]
    assert pairs(command, "-f") == ["Dockerfile.php"]
    assert pairs(command, "--target") == ["base"]
    assert pairs(command, "-t") == [ref]
    assert command[-1] == "."
    assert not any(a.startswith("--cache") for a in command)


def test_build_base_can_use_the_registry_cache_and_skip_layers(tmp_path):
    fake = FakeRunner()

    images.build_base(
        fake,
        tmp_path,
        REG,
        no_cache=True,
        registry_cache=True,
    )

    (command,) = fake.commands
    assert "--no-cache" in command
    assert pairs(command, "--cache-from") == [
        f"type=registry,ref={REG}/commons-wordpress-base:cache",
    ]


@pytest.mark.parametrize(
    ("tags", "expected_count"),
    [(MAIN_TAGS, 8), (PROD_TAGS, 4)],
)
def test_push_refs_cover_every_image_and_tag(tags, expected_count):
    refs = images.push_refs([NGINX, APP], REG, tags)

    assert len(refs) == expected_count
    assert f"{REG}/commons-wordpress-nginx:{tags.branch_tag}" in refs
    assert f"{REG}/commons-wordpress-app:{tags.build_tag}" in refs


def test_push_images_pushes_each_reference_in_order():
    fake = FakeRunner()

    images.push_images(fake, ["a:1", "b:2"])

    assert fake.commands == [
        ["docker", "push", "a:1"],
        ["docker", "push", "b:2"],
    ]
