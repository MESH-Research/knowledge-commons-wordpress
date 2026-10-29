import pytest

from kcbuild import config


def test_default_registry_is_the_kc_account_in_us_east_1():
    assert (
        config.ecr_registry() == "755997884632.dkr.ecr.us-east-1.amazonaws.com"
    )


def test_registry_can_be_built_for_another_account_and_region():
    assert (
        config.ecr_registry("123", "eu-west-2")
        == "123.dkr.ecr.eu-west-2.amazonaws.com"
    )


@pytest.mark.parametrize(
    ("branch", "expected"),
    [
        ("main", True),
        ("production", False),
        ("dev", False),
        ("staging", False),
        ("feature/x", False),
    ],
)
def test_only_main_is_the_release_branch(branch, expected):
    assert config.is_release_branch(branch) is expected


def test_all_images_are_returned_in_workflow_order_by_default():
    assert [img.name for img in config.get_images()] == ["nginx", "app"]


def test_images_can_be_selected_by_name():
    (image,) = config.get_images(["app"])
    assert image.repository == "commons-wordpress-app"
    assert image.dockerfile == "Dockerfile.php"
    assert image.target == "cloud"


def test_unknown_image_name_is_rejected():
    with pytest.raises(ValueError, match="cron"):
        config.get_images(["cron"])


def test_nginx_image_has_no_build_target_and_gets_app_env():
    (nginx,) = config.get_images(["nginx"])
    assert nginx.target is None
    assert nginx.passes_app_env is True
    assert nginx.uses_base is False
