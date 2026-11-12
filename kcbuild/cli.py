"""Click command line for the local CI stand-in.

Each job of ``.github/workflows/push-to-aws.yml`` is its own subcommand
(``build``, ``push``, ``bump``) so an image can be produced without pushing
it; ``release`` chains them in the order CI uses, and ``base`` is the
``build-base-image.yml`` workflow. The remaining commands run the PHP and
nginx tests and drive the ``docker-compose.test.yml`` stack.
"""

import functools
import platform
import sys
from collections.abc import Callable
from dataclasses import dataclass
from dataclasses import field
from pathlib import Path

import click

from kcbuild import aws
from kcbuild import images
from kcbuild import metadata
from kcbuild import quality
from kcbuild import stack
from kcbuild.config import AWS_REGION
from kcbuild.config import IMAGES
from kcbuild.config import TEST_COMPOSE_FILE
from kcbuild.config import Image
from kcbuild.config import ecr_registry
from kcbuild.config import get_images
from kcbuild.config import is_release_branch
from kcbuild.runner import CommandError
from kcbuild.runner import Runner

REPO_ROOT = Path(__file__).resolve().parent.parent
SUITE_CHOICES = (*quality.PHPUNIT_CONFIGS, "all", "none")


@dataclass
class AppContext:
    runner: Runner
    registry: str
    region: str
    profile: str | None
    repo: Path
    branch: str | None
    skip_bump: bool
    overrides: list[str] = field(default_factory=list)
    _info: metadata.BuildInfo | None = None

    def build_info(self) -> metadata.BuildInfo:
        """Resolve branch, sha and version once per invocation.

        On ``main`` this also asks commitizen whether the next push would
        release a new version, so the tags match what CI is about to apply.
        """
        if self._info is None:
            branch = self.branch or metadata.git_branch(self.runner, self.repo)
            next_version = None
            if is_release_branch(branch) and not self.skip_bump:
                next_version = quality.compute_next_version(
                    self.runner,
                    self.repo,
                    metadata.read_version_file(self.repo),
                )
            self._info = metadata.collect_build_info(
                self.runner,
                self.repo,
                branch=branch,
                next_version=next_version,
            )
        return self._info

    def tags(self) -> metadata.ImageTags:
        return metadata.compute_tags(self.build_info())

    def base_ref(self) -> str:
        return images.base_image_ref(self.registry)


pass_app = click.make_pass_decorator(AppContext)


def banner(text: str) -> None:
    click.secho(f"\n==> {text}", fg="cyan", bold=True)


def handle_errors(func: Callable[..., None]) -> Callable[..., None]:
    """Turn stage failures into a short message and a non-zero exit."""

    @functools.wraps(func)
    def wrapper(*args, **kwargs):
        try:
            return func(*args, **kwargs)
        except CommandError as exc:
            click.secho(f"\nFAILED: {exc}", fg="red", err=True)
            sys.exit(exc.result.returncode or 1)
        except aws.AwsAuthError as exc:
            click.secho(f"\nFAILED: {exc}", fg="red", err=True)
            sys.exit(2)
        except ValueError as exc:
            raise click.UsageError(str(exc)) from exc

    return wrapper


def warn_if_not_arm64() -> None:
    if platform.machine() not in {"aarch64", "arm64"}:
        click.secho(
            "Warning: CI builds these images on linux/arm64 but this host "
            f"is {platform.machine()}; docker will need binfmt/QEMU "
            "emulation and the result will not be byte-identical.",
            fg="yellow",
            err=True,
        )


@click.group()
@click.option(
    "--branch",
    default=None,
    help="Build as if this branch had been pushed (default: the checked-out "
    "branch). Only main receives :v<version> and :latest tags.",
)
@click.option(
    "--skip-bump",
    is_flag=True,
    help="On main, do not ask commitizen whether a release is pending.",
)
@click.option(
    "--profile",
    default=None,
    envvar="AWS_PROFILE",
    help="AWS CLI profile to use for ECR and ECS calls.",
)
@click.option("--region", default=AWS_REGION, show_default=True)
@click.option(
    "--registry",
    default=None,
    help="Override the ECR registry host (defaults to the KC account).",
)
@click.option(
    "--repo",
    type=click.Path(path_type=Path, file_okay=False),
    default=REPO_ROOT,
    help="Repository root (defaults to this checkout).",
)
@click.option(
    "-f",
    "--override",
    "overrides",
    multiple=True,
    help=f"Extra compose file layered on {TEST_COMPOSE_FILE} for every "
    "stack command (repeatable).",
)
@click.option(
    "--dry-run",
    is_flag=True,
    help="Print every command instead of running it.",
)
@click.pass_context
def main(
    ctx: click.Context,
    *,
    branch: str | None,
    skip_bump: bool,
    profile: str | None,
    region: str,
    registry: str | None,
    repo: Path,
    overrides: tuple[str, ...],
    dry_run: bool,
) -> None:
    """Build, test, push and run KC WordPress without GitHub Actions."""
    # Tests inject a fake runner through ``obj``; anything with a ``run``
    # method is accepted so the CLI never touches a real subprocess there.
    injected = ctx.obj if hasattr(ctx.obj, "run") else None
    ctx.obj = AppContext(
        runner=injected or Runner(dry_run=dry_run, echo=click.echo, cwd=repo),
        registry=registry or ecr_registry(region=region),
        region=region,
        profile=profile,
        repo=repo,
        branch=branch,
        skip_bump=skip_bump,
        overrides=list(overrides),
    )


image_option = click.option(
    "--image",
    "image_names",
    multiple=True,
    type=click.Choice(sorted(IMAGES)),
    help="Only this image (repeatable; default: both).",
)


# -- Information ----------------------------------------------------------


@main.command()
@pass_app
@handle_errors
def info(app: AppContext) -> None:
    """Show the version, tags and references this checkout would push."""
    build = app.build_info()
    tags = app.tags()
    click.echo(f"branch:      {build.branch}")
    click.echo(f"sha:         {build.sha}")
    click.echo(f"version:     {build.version}")
    if is_release_branch(build.branch):
        click.echo(
            "bump:        "
            + (
                "yes, commitizen will release this version"
                if build.bumped
                else "no (nothing semantic to bump)"
            ),
        )
    click.echo(f"registry:    {app.registry}")
    click.echo(f"base image:  {app.base_ref()}")
    click.echo(f"branch tag:  {tags.branch_tag}")
    click.echo(f"build tag:   {tags.build_tag}")
    click.echo(f"version tag: {tags.version_tag or '(main only)'}")
    click.echo(f"latest:      {'yes' if tags.latest else 'no (main only)'}")
    click.echo("would push:")
    for ref in images.push_refs(get_images(), app.registry, tags):
        click.echo(f"  {ref}")


# -- Lint and tests ---------------------------------------------------------


@main.command()
@pass_app
@handle_errors
def lint(app: AppContext) -> None:
    """Run phpcs through `composer test`."""
    banner("Lint (composer test)")
    quality.run_lint(app.runner, app.repo)
    click.secho("Lint passed.", fg="green")


def unit_test_stage(
    app: AppContext,
    suite: str,
    filter_pattern: str | None = None,
) -> None:
    suites = list(quality.PHPUNIT_CONFIGS) if suite == "all" else [suite]
    for name in suites:
        banner(f"PHPUnit: {name} ({quality.PHPUNIT_CONFIGS[name]})")
        quality.run_unit_tests(
            app.runner,
            app.repo,
            name,
            filter_pattern=filter_pattern,
        )


@main.command()
@click.option(
    "--suite",
    type=click.Choice(SUITE_CHOICES),
    default="all",
    show_default=True,
    help="Which PHPUnit suite to run.",
)
@click.option(
    "--filter",
    "filter_pattern",
    default=None,
    help="PHPUnit --filter pattern.",
)
@click.option(
    "--nginx",
    is_flag=True,
    help="Also run the nginx redirect tests (needs docker).",
)
@click.option(
    "--nginx-env",
    "nginx_envs",
    multiple=True,
    help="Limit the nginx tests to these environments.",
)
@click.option(
    "--e2e",
    is_flag=True,
    help="Also run the Playwright suite in the compose stack (slow).",
)
@pass_app
@handle_errors
def test(
    app: AppContext,
    *,
    suite: str,
    filter_pattern: str | None,
    nginx: bool,
    nginx_envs: tuple[str, ...],
    e2e: bool,
) -> None:
    """Run the PHPUnit suites, optionally the nginx and e2e tests too."""
    if suite != "none":
        unit_test_stage(app, suite, filter_pattern)
    if nginx:
        banner(f"nginx redirects ({quality.NGINX_TEST_SCRIPT})")
        quality.run_nginx_tests(app.runner, app.repo, list(nginx_envs) or None)
    if e2e:
        status = e2e_stage(app, build=True, setup=True, keep_up=False)
        if status:
            sys.exit(status)
    click.secho("Tests passed.", fg="green")


# -- Base image -------------------------------------------------------------


def login_stage(app: AppContext) -> None:
    banner(f"ECR login ({app.registry})")
    account = aws.caller_account(
        app.runner,
        profile=app.profile,
        region=app.region,
    )
    click.echo(f"AWS account: {account}")
    aws.ecr_login(
        app.runner,
        app.registry,
        profile=app.profile,
        region=app.region,
    )


def ensure_base(
    app: AppContext,
    *,
    rebuild: bool = False,
    pull: bool = False,
    no_cache: bool = False,
    registry_cache: bool = False,
    logged_in: bool = False,
) -> None:
    ref = app.base_ref()
    if pull:
        if not logged_in:
            login_stage(app)
        banner(f"Pull base image {ref}")
        images.pull_image(app.runner, ref)
        return
    if not rebuild and images.image_exists(app.runner, ref):
        click.echo(f"Base image {ref} already present; reusing it.")
        return
    if not rebuild:
        click.secho(
            f"Base image {ref} not found locally. Building it (a large "
            "download); pass --pull-base to fetch CI's copy instead.",
            fg="yellow",
            err=True,
        )
    banner(f"Build base image {ref} (Dockerfile.php --target base)")
    images.build_base(
        app.runner,
        app.repo,
        app.registry,
        no_cache=no_cache,
        registry_cache=registry_cache,
    )


@main.command()
@click.option(
    "--pull",
    is_flag=True,
    help="Pull the base image from ECR instead of building it.",
)
@click.option(
    "--push",
    "do_push",
    is_flag=True,
    help="Push the base image to ECR (what the build-base-image workflow "
    "does on main).",
)
@click.option("--no-cache", is_flag=True, help="Build without layer cache.")
@click.option(
    "--registry-cache",
    is_flag=True,
    help="Use the ECR :cache manifest as buildx cache (needs login).",
)
@pass_app
@handle_errors
def base(
    app: AppContext,
    *,
    pull: bool,
    do_push: bool,
    no_cache: bool,
    registry_cache: bool,
) -> None:
    """Build or pull the base image the app image is built FROM."""
    warn_if_not_arm64()
    needs_login = do_push or registry_cache
    if needs_login and not pull:
        login_stage(app)
    ensure_base(
        app,
        rebuild=True,
        pull=pull,
        no_cache=no_cache,
        registry_cache=registry_cache,
        logged_in=needs_login,
    )
    if do_push:
        banner(f"Push {app.base_ref()}")
        images.push_images(app.runner, [app.base_ref()])
    click.secho(f"Base image ready: {app.base_ref()}", fg="green")


# -- Build, push, bump ------------------------------------------------------


def build_stage(
    app: AppContext,
    *,
    selected: list[Image],
    rebuild_base: bool = False,
    pull_base: bool = False,
    no_cache: bool = False,
    registry_cache: bool = False,
) -> metadata.ImageTags:
    warn_if_not_arm64()
    if registry_cache:
        login_stage(app)
    if any(image.uses_base for image in selected):
        ensure_base(
            app,
            rebuild=rebuild_base,
            pull=pull_base,
            logged_in=registry_cache,
        )
    build = app.build_info()
    tags = app.tags()
    for image in selected:
        banner(
            f"Build {image.name} ({image.dockerfile}"
            f"{' --target ' + image.target if image.target else ''}) "
            f"tag={tags.build_tag}",
        )
        for ref in images.build_image(
            app.runner,
            app.repo,
            image,
            app.registry,
            build,
            tags,
            no_cache=no_cache,
            registry_cache=registry_cache,
        ):
            click.echo(f"  {ref}")
    return tags


@main.command()
@image_option
@click.option(
    "--rebuild-base",
    is_flag=True,
    help="Rebuild the base image even if it already exists locally.",
)
@click.option(
    "--pull-base",
    is_flag=True,
    help="Pull the base image from ECR (needs AWS credentials).",
)
@click.option("--no-cache", is_flag=True, help="Build without layer cache.")
@click.option(
    "--registry-cache",
    is_flag=True,
    help="Read and write the ECR :cache manifests as CI does (needs login).",
)
@pass_app
@handle_errors
def build(
    app: AppContext,
    *,
    image_names: tuple[str, ...],
    rebuild_base: bool,
    pull_base: bool,
    no_cache: bool,
    registry_cache: bool,
) -> None:
    """Build the nginx and app images with the tags CI would apply."""
    tags = build_stage(
        app,
        selected=get_images(list(image_names)),
        rebuild_base=rebuild_base,
        pull_base=pull_base,
        no_cache=no_cache,
        registry_cache=registry_cache,
    )
    click.secho(
        f"Built {app.build_info().branch} images: {tags.build_tag}",
        fg="green",
    )


@main.command()
@pass_app
@handle_errors
def login(app: AppContext) -> None:
    """Log docker in to ECR using the local AWS CLI."""
    login_stage(app)
    click.secho("Logged in.", fg="green")


def push_stage(
    app: AppContext,
    *,
    selected: list[Image],
    skip_login: bool = False,
    with_base: bool = False,
    label_cache: bool = False,
) -> list[str]:
    tags = app.tags()
    if not skip_login:
        login_stage(app)
    refs = images.push_refs(selected, app.registry, tags)
    if with_base:
        refs = [app.base_ref(), *refs]
    banner(f"Push {len(refs)} image references to ECR")
    images.push_images(app.runner, refs)
    if label_cache:
        banner("Label cache manifests")
        for image in selected:
            applied = aws.label_cache_manifest(
                app.runner,
                image.repository,
                tags.build_tag,
                profile=app.profile,
                region=app.region,
            )
            click.echo(
                f"  {image.repository}: "
                + (", ".join(applied) if applied else "tags already present"),
            )
    return refs


@main.command()
@image_option
@click.option("--skip-login", is_flag=True, help="Assume docker is logged in.")
@click.option(
    "--with-base",
    is_flag=True,
    help="Also push the base image first.",
)
@click.option(
    "--label-cache",
    is_flag=True,
    help="Re-tag the ECR :cache manifests as do-not-deploy-/build-cache-"
    "<build tag>, as CI does after a --registry-cache build.",
)
@pass_app
@handle_errors
def push(
    app: AppContext,
    *,
    image_names: tuple[str, ...],
    skip_login: bool,
    with_base: bool,
    label_cache: bool,
) -> None:
    """Push the images built by `build` to ECR."""
    refs = push_stage(
        app,
        selected=get_images(list(image_names)),
        skip_login=skip_login,
        with_base=with_base,
        label_cache=label_cache,
    )
    click.secho(f"Pushed {len(refs)} references.", fg="green")


def bump_stage(app: AppContext) -> bool:
    banner("Version bump (commitizen)")
    bumped = quality.bump_version(app.runner, app.repo)
    click.echo("Version bumped." if bumped else "Nothing to bump.")
    return bumped


@main.command()
@pass_app
@handle_errors
def bump(app: AppContext) -> None:
    """Bump VERSION and CHANGELOG with commitizen (the push-bump job)."""
    if bump_stage(app):
        click.secho(
            "Remember to `git push origin main --tags`.",
            fg="green",
        )


@main.command()
@click.option("--cluster", required=True, help="ECS cluster name.")
@click.option("--service", required=True, help="ECS service name.")
@pass_app
@handle_errors
def deploy(app: AppContext, *, cluster: str, service: str) -> None:
    """Force a new ECS deployment of a service."""
    banner(f"Deploy: force new deployment of {cluster}/{service}")
    aws.caller_account(app.runner, profile=app.profile, region=app.region)
    aws.force_deployment(
        app.runner,
        cluster=cluster,
        service=service,
        profile=app.profile,
        region=app.region,
    )
    click.secho("Deployment requested.", fg="green")


# -- Chained run --------------------------------------------------------------


@main.command()
@image_option
@click.option("--skip-tests", is_flag=True, help="Do not run PHPUnit first.")
@click.option("--skip-push", is_flag=True, help="Build only; implies no bump.")
@click.option(
    "--push-git",
    is_flag=True,
    help="After a bump on main, `git push origin main --tags`.",
)
@click.option("--rebuild-base", is_flag=True)
@click.option("--pull-base", is_flag=True)
@click.option("--no-cache", is_flag=True)
@click.option("--registry-cache", is_flag=True)
@pass_app
@handle_errors
def release(
    app: AppContext,
    *,
    image_names: tuple[str, ...],
    skip_tests: bool,
    skip_push: bool,
    push_git: bool,
    rebuild_base: bool,
    pull_base: bool,
    no_cache: bool,
    registry_cache: bool,
) -> None:
    """Test, build, push, then bump on main (the push-to-aws workflow)."""
    selected = get_images(list(image_names))
    if not skip_tests:
        unit_test_stage(app, "all")
    build = app.build_info()
    tags = build_stage(
        app,
        selected=selected,
        rebuild_base=rebuild_base,
        pull_base=pull_base,
        no_cache=no_cache,
        registry_cache=registry_cache,
    )
    if skip_push:
        click.secho(
            f"Built {build.branch} {tags.build_tag} (not pushed).",
            fg="green",
        )
        return
    push_stage(
        app,
        selected=selected,
        skip_login=registry_cache,
        label_cache=registry_cache,
    )
    # CI commits the bump only after every image push succeeded, so a
    # failed build never strands a version bump on main.
    if build.bumped and bump_stage(app) and push_git:
        banner(f"Push bump commit and tags to {build.branch}")
        quality.push_git(app.runner, app.repo, build.branch)
    click.secho(
        f"Release complete: {build.branch} {tags.build_tag}",
        fg="green",
    )


# -- Local stack --------------------------------------------------------------


def e2e_stage(
    app: AppContext,
    *,
    build: bool,
    setup: bool,
    keep_up: bool,
) -> int:
    banner(f"Playwright e2e ({TEST_COMPOSE_FILE})")
    status = 1
    try:
        stack.stack_up(app.runner, app.repo, app.overrides, build=build)
        if setup:
            stack.stack_setup(app.runner, app.repo, app.overrides)
        status = stack.stack_e2e(app.runner, app.repo, app.overrides)
        if status:
            click.secho(f"Playwright exited with {status}.", fg="red", err=True)
    finally:
        if not keep_up:
            stack.dump_php_logs(app.runner, app.repo, app.overrides)
            stack.stack_down(
                app.runner,
                app.repo,
                app.overrides,
                volumes=True,
            )
    return status


@main.command()
@click.option("--no-build", is_flag=True, help="Reuse the existing images.")
@click.option(
    "--no-setup",
    is_flag=True,
    help="Skip the WordPress/BuddyPress setup phases (stack already "
    "initialised).",
)
@pass_app
@handle_errors
def up(app: AppContext, *, no_build: bool, no_setup: bool) -> None:
    """Start the test stack on http://hcommons.test (host port 8080)."""
    banner(f"Local stack ({TEST_COMPOSE_FILE})")
    stack.stack_up(app.runner, app.repo, app.overrides, build=not no_build)
    if not no_setup:
        banner("WordPress install, BuddyPress init and configuration")
        stack.stack_setup(app.runner, app.repo, app.overrides)
    click.secho(
        "Stack is up: http://hcommons.test via localhost:8080 (send a "
        "`Host: hcommons.test` header or add it to /etc/hosts).\n"
        "Login bypass: /wp-login.php?secret_key=e2e-test-key-change-me",
        fg="green",
    )


@main.command()
@click.option(
    "--volumes",
    is_flag=True,
    help="Also remove the database and media volumes.",
)
@pass_app
@handle_errors
def down(app: AppContext, *, volumes: bool) -> None:
    """Stop the test stack."""
    stack.stack_down(app.runner, app.repo, app.overrides, volumes=volumes)
    click.secho("Stack stopped.", fg="green")


@main.command()
@click.argument("services", nargs=-1)
@click.option("--no-follow", is_flag=True, help="Print and exit.")
@pass_app
@handle_errors
def logs(
    app: AppContext, *, services: tuple[str, ...], no_follow: bool
) -> None:
    """Follow the test stack's logs."""
    stack.stack_logs(
        app.runner,
        app.repo,
        app.overrides,
        list(services) or None,
        follow=not no_follow,
    )


@main.command()
@click.option("--no-build", is_flag=True, help="Reuse the existing images.")
@click.option(
    "--no-setup",
    is_flag=True,
    help="Skip the WordPress/BuddyPress setup phases.",
)
@click.option(
    "--keep-up",
    is_flag=True,
    help="Leave the stack running afterwards instead of tearing it down.",
)
@pass_app
@handle_errors
def e2e(
    app: AppContext, *, no_build: bool, no_setup: bool, keep_up: bool
) -> None:
    """Run the Playwright suite in the compose stack (run-e2e.sh)."""
    status = e2e_stage(
        app,
        build=not no_build,
        setup=not no_setup,
        keep_up=keep_up,
    )
    if status:
        sys.exit(status)
    click.secho("E2E tests passed.", fg="green")


@main.command(context_settings={"ignore_unknown_options": True})
@click.argument("args", nargs=-1, type=click.UNPROCESSED)
@pass_app
@handle_errors
def wp(app: AppContext, *, args: tuple[str, ...]) -> None:
    """Run WP-CLI inside the running app container."""
    status = stack.stack_wp(app.runner, app.repo, app.overrides, list(args))
    if status:
        sys.exit(status)


@main.command()
@click.option(
    "--service",
    default="app",
    show_default=True,
    help="Compose service to open a shell in.",
)
@pass_app
@handle_errors
def shell(app: AppContext, *, service: str) -> None:
    """Open an interactive shell in a running stack container."""
    shell_binary = "bash" if service == "app" else "sh"
    status = stack.stack_exec(
        app.runner,
        app.repo,
        app.overrides,
        service,
        [shell_binary],
        check=False,
    )
    if status:
        sys.exit(status)
