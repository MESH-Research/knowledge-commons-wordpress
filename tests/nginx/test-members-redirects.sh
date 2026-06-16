#!/usr/bin/env bash
#
# Behavioural test for the /members/ -> Profiles redirects in
# config/*/nginx/templates/25-buddypress-redirects.conf.template.
#
# Runs the real nginx.conf + templates (merged the same way Dockerfile.nginx
# merges them) inside the stock nginx image, then asserts on the Location
# headers nginx returns for various Host/path combinations.
#
# Usage: tests/nginx/test-members-redirects.sh [env ...]
#        (defaults to all of: production dev staging test)

set -u

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
NGINX_IMAGE="nginx:stable-alpine3.19"
CONTAINER_NAME="kc-nginx-redirect-test"
HOST_PORT=8089

ENVS=("$@")
[ ${#ENVS[@]} -eq 0 ] && ENVS=(production dev staging test)

profile_domain() {
    case "$1" in
        production) echo "profile.hcommons.org" ;;
        dev)        echo "profile.hcommons-dev.org" ;;
        staging)    echo "profile.hcommons-staging.org" ;;
        test)       echo "profile.hcommons-test.org" ;;
        *)          echo "unknown-env" ;;
    esac
}

site_domain() {
    # The DOMAIN_NAME env var each deployment runs with.
    case "$1" in
        production) echo "hcommons.org" ;;
        dev)        echo "hcommons-dev.org" ;;
        staging)    echo "hcommons-staging.org" ;;
        test)       echo "hcommons-test.org" ;;
        *)          echo "unknown-env" ;;
    esac
}

# Custom domains that live outside DOMAIN_NAME but belong to a network,
# as host=network pairs. These mirror the entries in
# config/<env>/nginx/templates/05-network-domain-aliases.conf.template.
alias_entries() {
    case "$1" in
        production) echo "commons.msu.edu=msu action.mla.org=mla symposium.mla.org=mla" ;;
        dev)        echo "msucommons-dev.org=msu" ;;
        *)          echo "" ;;
    esac
}

PASS=0
FAIL=0

cleanup() {
    docker rm -f "$CONTAINER_NAME" >/dev/null 2>&1
    [ -n "${TMPDIR_TEMPLATES:-}" ] && rm -rf "$TMPDIR_TEMPLATES"
}
trap cleanup EXIT

start_nginx() {
    local env="$1" domain="$2"

    cleanup
    TMPDIR_TEMPLATES="$(mktemp -d)"
    mkdir -p "$TMPDIR_TEMPLATES/templates"
    # Replicate the Dockerfile.nginx overlay: base templates, then env overlay.
    cp -a "$REPO_ROOT/config/all/nginx/templates/." "$TMPDIR_TEMPLATES/templates/"
    if [ -d "$REPO_ROOT/config/$env/nginx/templates" ]; then
        cp -a "$REPO_ROOT/config/$env/nginx/templates/." "$TMPDIR_TEMPLATES/templates/"
    fi

    docker run -d --name "$CONTAINER_NAME" \
        -e "DOMAIN_NAME=$domain" \
        --add-host app:127.0.0.1 \
        -v "$REPO_ROOT/config/all/nginx/nginx.conf:/etc/nginx/nginx.conf:ro" \
        -v "$TMPDIR_TEMPLATES/templates:/etc/nginx/templates:ro" \
        -p "127.0.0.1:$HOST_PORT:80" \
        "$NGINX_IMAGE" >/dev/null || return 1

    # Wait for nginx to answer.
    for _ in $(seq 1 30); do
        if curl -s -o /dev/null "http://127.0.0.1:$HOST_PORT/" 2>/dev/null; then
            return 0
        fi
        sleep 0.5
    done
    echo "nginx did not become ready for env=$env" >&2
    docker logs "$CONTAINER_NAME" >&2
    return 1
}

# assert_redirect <env> <host> <path> <expected-location>
assert_redirect() {
    local env="$1" host="$2" path="$3" expected="$4"
    local out code location
    out="$(curl -s -o /dev/null -w '%{http_code}\t%{redirect_url}' \
        -H "Host: $host" "http://127.0.0.1:$HOST_PORT$path")"
    code="${out%%	*}"
    location="${out#*	}"
    if [ "$code" = "301" ] && [ "$location" = "$expected" ]; then
        PASS=$((PASS + 1))
        echo "PASS [$env] $host$path -> $location"
    else
        FAIL=$((FAIL + 1))
        echo "FAIL [$env] $host$path"
        echo "     expected: 301 $expected"
        echo "     got:      $code ${location:-<none>}"
    fi
}

# assert_not_redirected_to_profiles <env> <host> <path>
assert_not_redirected_to_profiles() {
    local env="$1" host="$2" path="$3"
    local out code location
    out="$(curl -s -o /dev/null -w '%{http_code}\t%{redirect_url}' \
        -H "Host: $host" "http://127.0.0.1:$HOST_PORT$path")"
    code="${out%%	*}"
    location="${out#*	}"
    case "$location" in
        https://profile.*|https://*.profile.*)
            FAIL=$((FAIL + 1))
            echo "FAIL [$env] $host$path unexpectedly redirected to $location"
            ;;
        *)
            PASS=$((PASS + 1))
            echo "PASS [$env] $host$path stays in WordPress (status $code)"
            ;;
    esac
}

for env in "${ENVS[@]}"; do
    domain="$(site_domain "$env")"
    profile="$(profile_domain "$env")"
    echo "=== env: $env (DOMAIN_NAME=$domain -> $profile) ==="

    start_nginx "$env" "$domain" || { FAIL=$((FAIL + 1)); continue; }

    # Network subdomain: members directory is scoped to the network via a
    # matching subdomain on the Profiles host.
    assert_redirect "$env" "stemedplus.$domain" "/members/" \
        "https://stemedplus.$profile/members/"

    # Deeper member path keeps the full request URI.
    assert_redirect "$env" "stemedplus.$domain" "/members/somebody/" \
        "https://stemedplus.$profile/members/somebody/"

    # Query strings survive the redirect.
    assert_redirect "$env" "stemedplus.$domain" "/members/?page=2" \
        "https://stemedplus.$profile/members/?page=2"

    # A site within a network still resolves to the network.
    assert_redirect "$env" "asite.stemedplus.$domain" "/members/" \
        "https://stemedplus.$profile/members/"

    # Bare domain (no subdomain): unscoped members directory.
    assert_redirect "$env" "$domain" "/members/" \
        "https://$profile/members/"

    # Excluded BuddyPress member sub-pages stay in WordPress.
    assert_not_redirected_to_profiles "$env" "stemedplus.$domain" \
        "/members/somebody/activity/"

    # Registration redirect is unchanged by the network scoping.
    assert_redirect "$env" "stemedplus.$domain" "/membership/" \
        "https://$profile/registration/start/"

    # A foreign domain with no alias entry gets the unscoped redirect.
    assert_redirect "$env" "unrelated-domain.org" "/members/" \
        "https://$profile/members/"

    # Custom domains listed in 05-network-domain-aliases translate to
    # their network's subdomain on the Profiles host.
    for entry in $(alias_entries "$env"); do
        alias="${entry%%=*}"
        network="${entry##*=}"

        assert_redirect "$env" "$alias" "/members/" \
            "https://$network.$profile/members/"

        assert_redirect "$env" "$alias" "/members/?page=2" \
            "https://$network.$profile/members/?page=2"

        # Sub-sites of the custom domain resolve to the same network.
        assert_redirect "$env" "asite.$alias" "/members/" \
            "https://$network.$profile/members/"

        # Member sub-pages on the custom domain stay in WordPress too.
        assert_not_redirected_to_profiles "$env" "$alias" \
            "/members/somebody/activity/"
    done

    if [ "$env" = "production" ]; then
        # iteach.msu.edu is wholesale-redirected to iteach.commons.msu.edu
        # by 40-redirects.conf.template, so it reaches the network-scoped
        # Profiles redirect in two hops via the .commons.msu.edu alias.
        assert_redirect "$env" "iteach.msu.edu" "/members/" \
            "https://iteach.commons.msu.edu/members/"
        assert_redirect "$env" "iteach.commons.msu.edu" "/members/" \
            "https://msu.$profile/members/"
    fi
done

echo
echo "passed: $PASS  failed: $FAIL"
[ "$FAIL" -eq 0 ]
