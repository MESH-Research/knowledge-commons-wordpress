import pytest

from kcbuild import aws
from tests.kcbuild.support import FakeRunner
from tests.kcbuild.support import answer

REG = "755997884632.dkr.ecr.us-east-1.amazonaws.com"


def test_environment_sets_region_and_optional_profile():
    assert aws.aws_environment(None, "us-east-1") == {
        "AWS_REGION": "us-east-1",
        "AWS_DEFAULT_REGION": "us-east-1",
    }
    assert aws.aws_environment("kc", "us-east-1")["AWS_PROFILE"] == "kc"


def test_caller_account_returns_the_account_id():
    fake = FakeRunner(
        answer(("aws", "sts", "get-caller-identity"), 0, "755997884632\n"),
    )

    account = aws.caller_account(fake, profile="kc", region="us-east-1")

    assert account == "755997884632"
    assert fake.calls[0].env["AWS_PROFILE"] == "kc"


def test_missing_credentials_raise_a_friendly_error():
    fake = FakeRunner(answer(("aws", "sts", "get-caller-identity"), 255, ""))

    with pytest.raises(aws.AwsAuthError, match="aws login"):
        aws.caller_account(fake, profile=None, region="us-east-1")


def test_ecr_login_pipes_the_token_into_docker_login():
    fake = FakeRunner(answer(("aws", "ecr", "get-login-password"), 0, "tok\n"))

    aws.ecr_login(fake, REG, profile=None, region="us-east-1")

    (login,) = fake.commands_starting("docker", "login")
    assert login == [
        "docker",
        "login",
        "--username",
        "AWS",
        "--password-stdin",
        REG,
    ]
    assert fake.calls[-1].stdin == "tok"
    assert "tok" not in login


def test_label_cache_manifest_retags_the_cache_manifest():
    fake = FakeRunner(
        answer(
            (
                "aws",
                "ecr",
                "batch-get-image",
                "--repository-name",
                "commons-wordpress-app",
                "--image-ids",
                "imageTag=cache",
                "--query",
                "images[0].imageManifest",
            ),
            0,
            '{"manifest": true}\n',
        ),
        answer(
            (
                "aws",
                "ecr",
                "batch-get-image",
                "--repository-name",
                "commons-wordpress-app",
                "--image-ids",
                "imageTag=cache",
                "--query",
                "images[0].imageManifestMediaType",
            ),
            0,
            "application/vnd.oci.image.index.v1+json\n",
        ),
    )

    applied = aws.label_cache_manifest(
        fake,
        "commons-wordpress-app",
        "v1.2.1-0123456",
        profile=None,
        region="us-east-1",
    )

    assert applied == [
        "do-not-deploy-v1.2.1-0123456",
        "build-cache-v1.2.1-0123456",
    ]
    puts = fake.commands_starting("aws", "ecr", "put-image")
    assert len(puts) == 2
    for put, tag in zip(puts, applied, strict=True):
        assert put[put.index("--image-tag") + 1] == tag
        assert put[put.index("--image-manifest") + 1] == '{"manifest": true}'
        assert put[put.index("--image-manifest-media-type") + 1] == (
            "application/vnd.oci.image.index.v1+json"
        )
        assert (
            put[put.index("--repository-name") + 1] == "commons-wordpress-app"
        )


def test_label_cache_manifest_tolerates_an_already_applied_tag():
    fake = FakeRunner(
        answer(("aws", "ecr", "batch-get-image"), 0, "m\n"),
        answer(("aws", "ecr", "put-image"), 254, ""),
    )

    applied = aws.label_cache_manifest(
        fake,
        "commons-wordpress-nginx",
        "t",
        profile=None,
        region="us-east-1",
    )

    assert applied == []


def test_force_deployment_updates_the_named_service():
    fake = FakeRunner()

    aws.force_deployment(
        fake,
        cluster="wordpress-prod",
        service="wp-green",
        profile="kc",
        region="us-east-1",
    )

    (command,) = fake.commands
    assert command[:3] == ["aws", "ecs", "update-service"]
    assert command[command.index("--cluster") + 1] == "wordpress-prod"
    assert command[command.index("--service") + 1] == "wp-green"
    assert "--force-new-deployment" in command
    assert fake.calls[0].env["AWS_PROFILE"] == "kc"
