# ECS Task Definition Health Checks

Recommendations for adding container-level health checks to the WordPress ECS
task definitions (`app` + `nginx`). As of task definition revision 17 (dev),
neither container defines a `healthCheck`, neither Dockerfile contains a
`HEALTHCHECK` instruction, and there is no endpoint suitable for probing.

## Current state

- **app** (`Dockerfile.php`) is `php:8.2.26-fpm-alpine3.20` running php-fpm on
  port 9000. No fcgi probe tools are installed, and
  `config/all/php/www.conf` does not configure `ping.path`.
- **nginx** (`Dockerfile.nginx`) is `nginx:stable-alpine3.19` and listens on
  **port 80 only**. TLS is terminated upstream, so the 443 port mapping in the
  task definition has no listener behind it — do not health check 443.
- Every `server` block in
  `config/all/nginx/templates/hcommons.conf.template` either redirects or
  serves WordPress; there is no plain 200 endpoint. The existing
  `location ~ ^/status.php` block has no `fastcgi_pass`, so it falls through
  to a static file lookup and effectively 404s.
- Both entrypoints fetch values from AWS Secrets Manager at startup, and the
  app container additionally symlinks EFS-hosted plugins/themes before
  starting php-fpm. Startup can take tens of seconds, so `startPeriod`
  matters.
- Both images are Alpine-based, so busybox `wget` is available in the nginx
  container without installing anything.

## 1. app container — php-fpm ping over FastCGI

### Image change (`Dockerfile.php`)

Install the `fcgi` package, which provides `cgi-fcgi`:

```dockerfile
RUN apk add --no-cache fcgi
```

### Pool change (`config/all/php/www.conf`)

Enable php-fpm's built-in ping endpoint:

```ini
ping.path = /ping
ping.response = pong
```

### Task definition

```json
"healthCheck": {
    "command": ["CMD-SHELL",
        "REQUEST_METHOD=GET SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong"],
    "interval": 30,
    "timeout": 5,
    "retries": 3,
    "startPeriod": 90
}
```

This exercises a worker over the FastCGI protocol rather than just checking
that the process exists.

**Zero-image-change stopgap:** `bash` is installed in the app image, so
`bash -c 'exec 3<>/dev/tcp/127.0.0.1/9000'` works as a health check command
with no image changes. It only proves the master socket is listening — not
that workers can serve — so treat it as temporary.

**Note on the listen address:** `www.conf` sets `listen = 127.0.0.1:9000`,
but the official php-fpm image's `zz-docker.conf` loads after it and
overrides the listen address to all interfaces — that is why nginx can reach
`app:9000` at all. The health check probes 127.0.0.1 from inside the
container, so it works either way.

## 2. nginx container — dedicated /healthz location

### Config change (`config/all/nginx/templates/hcommons.conf.template`)

Add an exact-match location to the `default_server` block. Exact matches take
precedence over the regex locations, and the default server block has no
server-level redirect, so a request for `http://127.0.0.1/healthz` lands
there and returns 200 rather than a 301:

```nginx
location = /healthz {
    access_log off;
    return 200 "ok";
}
```

### Task definition

Busybox `wget` exits non-zero on any non-2xx response:

```json
"healthCheck": {
    "command": ["CMD-SHELL", "wget -q -T 4 -O /dev/null http://127.0.0.1/healthz || exit 1"],
    "interval": 30,
    "timeout": 5,
    "retries": 3,
    "startPeriod": 60
}
```

This deliberately checks only nginx itself, not the path through to php-fpm.
The app container has its own check, and coupling the two would cause nginx
to flap whenever the app restarts.

## 3. Container ordering via dependsOn

The staging task definition already uses `dependsOn` with condition `START`
on nginx; the dev task definition has none. Once the app container has a
health check, upgrade the condition so nginx does not start (and snapshot
`volumesFrom`) until php-fpm is genuinely serving:

```json
"dependsOn": [
    { "containerName": "app", "condition": "HEALTHY" }
]
```

## Operational caveats

- Container health checks run via `docker exec` on the instance, and a
  failing check on an `essential` container kills the whole task. The
  generous `startPeriod` values above (covering the Secrets Manager fetch and
  EFS symlinking) are load-bearing, not decoration.
- If the ECS service sits behind an ALB, review the service's
  `healthCheckGracePeriodSeconds` as well — ALB target health is judged
  separately from these container checks.
