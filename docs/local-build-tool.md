# Local build tool (`kcb.py`)

`kcb.py` is a local stand-in for the two GitHub Actions workflows in
`.github/workflows/`. It builds, tags and pushes the same images CI does,
runs the test suites, and drives the `docker-compose.test.yml` stack. Every
external step is an ordinary `docker`, `aws`, `git` or `composer` command,
so `--dry-run` shows exactly what would happen.

Requirements: [uv](https://docs.astral.sh/uv/) (the script declares its own
dependencies), Docker with buildx, the AWS CLI with credentials for the KC
account (`aws login` or `aws sso login`), and `composer install` for the
PHPUnit commands. Builds should run on an arm64 host, as the CI runners do.

```sh
uv run kcb.py --help
```

## What CI does, and the matching commands

`push-to-aws.yml` runs on every push to `main`, `production`, `dev`,
`staging` and `test`:

1. **prepare-version** resolves the version with
   `scripts/build-scripts/resolve-build-version.sh`. On `main` it first asks
   commitizen (`cz bump --get-next`) whether the push will release a new
   version and, if so, builds with that version.
2. **build-nginx** and **build-app** build `Dockerfile.nginx` and
   `Dockerfile.php --target cloud` (the latter `FROM` the prebuilt
   `commons-wordpress-base:latest` image) and push them with these tags:
   - `<branch>` on every branch
   - `v<version>-<sha7>` on `main`, `<branch>-v<version>-<sha7>` elsewhere
   - `v<version>` and `latest` on `main` only
3. **push-bump** runs `cz bump --yes` and pushes the bump commit and tag to
   `main`, but only after both images were pushed.

`build-base-image.yml` builds `Dockerfile.php --target base` and pushes it as
`commons-wordpress-base:latest` whenever `Dockerfile.php` changes on `main`.

| CI job | `kcb.py` command |
| --- | --- |
| prepare-version | `info` (computed automatically by every build) |
| build-nginx / build-app, without the push | `build` |
| the push half of those jobs | `push` |
| push-bump | `bump` (or `release --push-git`) |
| the whole workflow in order | `release` |
| build-base-image | `base --push` |

Tags are computed from the checked-out branch. Pass `--branch <name>` to
build as if that branch had been pushed, for example to produce
`production` images from a feature checkout, or `--branch main` to preview
the version a release would carry.

## Common invocations

```sh
uv run kcb.py info                       # version, tags and references for this checkout
uv run kcb.py --dry-run release          # print every command CI would run
uv run kcb.py build                      # build nginx + app, tagged as CI would
uv run kcb.py build --image app          # just one image
uv run kcb.py build --pull-base          # fetch CI's base image instead of building it
uv run kcb.py push                       # ECR login, then push every tag
uv run kcb.py release                    # phpunit, build, push (then bump on main)
uv run kcb.py release --skip-tests       # same without the unit tests
uv run kcb.py --branch production build  # production tags from any checkout
uv run kcb.py base                       # build commons-wordpress-base locally
uv run kcb.py base --push                # ...and push it, as build-base-image does
uv run kcb.py deploy --cluster C --service S   # aws ecs update-service --force-new-deployment
```

### The base image and bandwidth

The app image is built `FROM` `commons-wordpress-base:latest`. `build`
reuses that image when it is already present locally, otherwise it builds
it from the `base` stage, which downloads PHP, every extension and the
Alpine packages. `--pull-base` downloads CI's copy from ECR instead, which
is usually smaller than a from-scratch build. Neither is cheap on a slow
connection; the base rarely changes, so once it exists locally it is
reused until `--rebuild-base` is passed.

### Registry cache

CI uses the `:cache` manifest in each ECR repository as a BuildKit cache.
`build --registry-cache` does the same (`--cache-from` and `--cache-to`),
and `push --label-cache` re-tags the cache manifest as
`do-not-deploy-<build tag>` and `build-cache-<build tag>`, as the workflow's
"Label cache manifest" step does. Exporting a registry cache needs a buildx
builder that supports it (the `docker-container` driver); the default
`docker` driver only imports.

### Version bumps on `main`

Versioning is owned by `main`: `VERSION` and `CHANGELOG.md` are updated by
commitizen (`.cz.toml`) and the bump commit is tagged `v<version>`. Other
branches never receive those commits, so their `VERSION` file goes stale
and the resolve script reports the highest released `v*` tag instead.

On `main`, `info`, `build` and `release` ask commitizen whether a release is
pending and build with the next version so the tags match what CI is about
to push. `release` then runs the bump itself, after the pushes succeed,
and `--push-git` pushes the bump commit and tag. `--skip-bump` disables
the check; `--skip-push` builds only and never bumps.

## Tests

```sh
uv run kcb.py test                       # both PHPUnit suites (site + ci-logon)
uv run kcb.py test --suite site --filter Docs
uv run kcb.py test --nginx               # plus tests/nginx/test-members-redirects.sh
uv run kcb.py test --nginx --suite none  # only the nginx checks
uv run kcb.py test --e2e                 # plus the Playwright run in the compose stack
uv run kcb.py lint                       # composer test (phpcs)
```

## The local stack

These wrap `docker-compose.test.yml`, following the phases of
`scripts/test-scripts/run-e2e.sh`: build, start `db`, `app` and `nginx`,
install WordPress (`setup`), let BuddyPress initialise itself through two
web requests, then run `setup-bp.sh`.

```sh
uv run kcb.py up                         # build, start and initialise the stack
uv run kcb.py up --no-build --no-setup   # restart an already-initialised stack
uv run kcb.py logs app                   # follow a service's logs
uv run kcb.py wp plugin list             # WP-CLI inside the app container
uv run kcb.py shell                      # bash in the app container
uv run kcb.py e2e                        # full Playwright run, then tear down
uv run kcb.py e2e --keep-up              # leave the stack running afterwards
uv run kcb.py down                       # stop; add --volumes to drop the database
```

The site answers on `http://localhost:8080` with a `Host: hcommons.test`
header (or add `hcommons.test` to `/etc/hosts`). The secret-key login
bypass is `/wp-login.php?secret_key=e2e-test-key-change-me`.

Bind-mount a working-tree plugin or a sibling theme repository over the
baked copy with an override file, and pass it with `-f` so **every** compose
command sees it (`docker compose run` recreates dependent services and
would otherwise drop the mount):

```yaml
# local.yml
services:
  app:
    volumes:
      - ./plugins/hc-custom:/app/plugins/hc-custom
  nginx:
    volumes:
      - ./plugins/hc-custom:/app/plugins/hc-custom:ro
```

```sh
uv run kcb.py -f local.yml up --no-build
```

## Tests for the tool itself

```sh
uv run pytest            # tests/kcbuild
uv run ruff check .
```

All external commands go through `kcbuild/runner.py`, and the tests
substitute a fake runner, so the suite never touches Docker, AWS or git.
