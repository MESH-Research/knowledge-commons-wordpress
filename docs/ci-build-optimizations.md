# CI/CD Build Optimizations

Related issue: [MESH-Research/knowledge-commons-wordpress#62](https://github.com/MESH-Research/knowledge-commons-wordpress/issues/62)

## Problem

The GitHub Actions build workflow (`push-to-aws.yml`) was taking approximately 45 minutes per push. The root causes were:

1. **QEMU ARM64 emulation** on x86 runners added a 3-5x overhead to every compile step
2. **Zero caching** meant every build compiled PHP extensions, downloaded 298 composer packages, and ran 4 npm builds from scratch
3. **Poor Dockerfile layer ordering** copied source code before dependency installs, so any code change invalidated all cached layers

## Optimizations Applied

### 1. Native ARM64 Runners

Switched from `ubuntu-latest` (x86 with QEMU emulation) to `ubuntu-24.04-arm` (native ARM64). This eliminates the emulation penalty for all CPU-intensive steps: PHP extension compilation, composer autoloader optimization, webpack builds, and gulp sass compilation.

### 2. Parallel Image Builds

Split the single sequential job into two parallel jobs — one for nginx, one for the PHP app. Since the images are independent, they now build simultaneously.

### 3. ECR Registry Layer Caching

Added Docker BuildKit layer caching using ECR as the cache backend:

```yaml
cache-from: type=registry,ref=<ECR>/commons-wordpress-app:cache
cache-to: type=registry,ref=<ECR>/commons-wordpress-app:cache,mode=max
```

This stores all intermediate build layers in ECR. On subsequent builds, unchanged layers are pulled from cache instead of being rebuilt. ECR was chosen over the GitHub Actions cache (`type=gha`) because the image layers exceed GitHub's 10GB cache limit.

### 4. Dockerfile Layer Reordering

Restructured the `cloud` stage in `Dockerfile.php` to maximize cache hit rate:

1. Directory structure and config (rarely changes)
2. Composer manifests only (`composer.json` + `composer.lock`)
3. Sub-project composer manifests (mailchimp, dahd-tainacan, etc.)
4. `composer install` (cached when lockfiles unchanged)
5. npm builds for composer-installed packages (kcworks-on-wp, cc-client)
6. Full source code COPY (scripts, plugins, mu-plugins, themes)
7. Theme gulp builds (boss-child, boss-child-refresh)
8. Symlinks

Previously, all source code was copied before `composer install`, meaning any code change invalidated the dependency layer. Now, a typical code-only change only triggers steps 6-8, which are fast file copies and symlinks.

### 5. BuildKit Cache Mounts

Added `--mount=type=cache` directives for composer and npm package caches:

```dockerfile
RUN --mount=type=cache,target=/home/www-data/.composer/cache,uid=82,gid=82 \
    composer install ...

RUN --mount=type=cache,target=/home/www-data/.npm,uid=82,gid=82 \
    npm ci && npm run build
```

Even when the dependency layer is invalidated (e.g., adding a new composer package), the downloaded archives are reused from the BuildKit cache rather than re-downloaded.

The `--no-cache` flag was also removed from all composer install commands — it was disabling composer's internal package cache, forcing re-downloads.

### 6. Pre-built Base Image

The `base` stage (PHP extensions, system packages, composer binary, wp-cli) changes rarely but takes 5-10 minutes to build even on native ARM64. It is now built separately via `.github/workflows/build-base-image.yml` and pushed to `commons-wordpress-base:latest` in ECR.

The main workflow passes this as a build arg:

```yaml
build-args: |
  BASE_IMAGE=<ECR>/commons-wordpress-base:latest
```

The `Dockerfile.php` uses a global ARG with a local fallback:

```dockerfile
ARG BASE_IMAGE=base
FROM ${BASE_IMAGE} AS cloud
```

This means local development (`docker build --target cloud`) still works using the inline `base` stage, while CI uses the pre-built image.

The base image workflow triggers on:
- Changes to `Dockerfile.php` pushed to `main`
- Manual dispatch (Actions > Build Base Image > Run workflow)

### 7. Docker Context Reduction

Expanded `.dockerignore` to exclude files not needed in the production image:

```
tests/
*.md
.github/
.lando.yml
docker-compose*.yml
docs/
```

### 8. Modernized GitHub Actions

Updated deprecated action versions:
- `aws-actions/configure-aws-credentials` v1 to v4
- `aws-actions/amazon-ecr-login` v1 to v2
- Replaced raw `docker buildx build` commands with `docker/build-push-action@v5`

## Expected Build Times

| Scenario | Before | After |
|----------|--------|-------|
| Typical push (code only) | ~45 min | ~2-3 min |
| Dependency change (composer.lock) | ~45 min | ~5-7 min |
| Cold build (no cache) | ~45 min | ~8-12 min |

## ECR Repositories

The following ECR repositories are used:

| Repository | Purpose |
|------------|---------|
| `commons-wordpress-app` | Production PHP-FPM app image |
| `commons-wordpress-nginx` | Production nginx image |
| `commons-wordpress-base` | Pre-built PHP base image (extensions, system packages) |

Each app/nginx repository also stores a `:cache` tag containing the BuildKit layer cache manifest.

## Maintenance

- **Adding a PHP extension or system package:** Run the "Build Base Image" workflow manually after merging the Dockerfile change to `main`.
- **Adding a composer package:** No action needed — the composer layer will rebuild on next push, and the new packages will be cached for subsequent builds.
- **Adding a new sub-project with composer.json:** Add a `COPY` for its `composer.json`/`composer.lock` in the manifests section of `Dockerfile.php` (before the `composer install` RUN), and add the corresponding install command with `--no-scripts`.
