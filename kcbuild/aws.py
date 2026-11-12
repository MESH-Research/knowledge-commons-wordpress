"""AWS operations: credential check, ECR login, cache-manifest labelling
and ECS redeploy, all through the locally installed AWS CLI.

These replace the ``configure-aws-credentials`` and ``amazon-ecr-login``
actions and the "Label cache manifest" steps of the workflow.
"""

from kcbuild.runner import Runner

CACHE_LABEL_PREFIXES = ("do-not-deploy", "build-cache")


class AwsAuthError(RuntimeError):
    """The local AWS CLI has no usable credentials."""


def aws_environment(profile: str | None, region: str) -> dict[str, str]:
    env = {"AWS_REGION": region, "AWS_DEFAULT_REGION": region}
    if profile:
        env["AWS_PROFILE"] = profile
    return env


def caller_account(
    runner: Runner,
    *,
    profile: str | None,
    region: str,
) -> str:
    result = runner.run(
        [
            "aws",
            "sts",
            "get-caller-identity",
            "--query",
            "Account",
            "--output",
            "text",
        ],
        env=aws_environment(profile, region),
        capture=True,
        check=False,
    )
    if result.returncode != 0:
        msg = (
            "No valid AWS credentials. Run `aws login` (or `aws sso login`) "
            "and retry, or pass --profile.\n"
            f"{result.stderr.strip()}"
        )
        raise AwsAuthError(msg)
    return result.stdout.strip()


def ecr_login(
    runner: Runner,
    registry: str,
    *,
    profile: str | None,
    region: str,
) -> None:
    env = aws_environment(profile, region)
    password = runner.run(
        ["aws", "ecr", "get-login-password", "--region", region],
        env=env,
        capture=True,
    ).stdout.strip()
    runner.run(
        ["docker", "login", "--username", "AWS", "--password-stdin", registry],
        stdin=password,
    )


def _cache_manifest_field(
    runner: Runner,
    repository: str,
    query: str,
    env: dict[str, str],
) -> str:
    return runner.run(
        [
            "aws",
            "ecr",
            "batch-get-image",
            "--repository-name",
            repository,
            "--image-ids",
            "imageTag=cache",
            "--query",
            query,
            "--output",
            "text",
        ],
        env=env,
        capture=True,
    ).stdout.strip()


def label_cache_manifest(
    runner: Runner,
    repository: str,
    build_tag: str,
    *,
    profile: str | None,
    region: str,
) -> list[str]:
    """Give the ``:cache`` manifest build-unique tags, as CI does.

    Buildkit tags only the cache config manifest, which ECR shows as an
    untagged artifact. Re-putting it under ``do-not-deploy-<tag>`` and
    ``build-cache-<tag>`` labels it. A tag already on the manifest makes
    ``put-image`` fail, which is a no-op here, as in the workflow.
    """
    env = aws_environment(profile, region)
    manifest = _cache_manifest_field(
        runner,
        repository,
        "images[0].imageManifest",
        env,
    )
    media_type = _cache_manifest_field(
        runner,
        repository,
        "images[0].imageManifestMediaType",
        env,
    )
    applied = []
    for prefix in CACHE_LABEL_PREFIXES:
        tag = f"{prefix}-{build_tag}"
        result = runner.run(
            [
                "aws",
                "ecr",
                "put-image",
                "--repository-name",
                repository,
                "--image-manifest",
                manifest,
                "--image-manifest-media-type",
                media_type,
                "--image-tag",
                tag,
            ],
            env=env,
            capture=True,
            check=False,
        )
        if result.returncode == 0:
            applied.append(tag)
    return applied


def force_deployment(
    runner: Runner,
    *,
    cluster: str,
    service: str,
    profile: str | None,
    region: str,
) -> None:
    runner.run(
        [
            "aws",
            "ecs",
            "update-service",
            "--cluster",
            cluster,
            "--service",
            service,
            "--force-new-deployment",
        ],
        env=aws_environment(profile, region),
    )
