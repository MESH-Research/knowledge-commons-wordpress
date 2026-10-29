#!/usr/bin/env bash
#
# Behavioural test for scripts/build-scripts/install-extra-php-extensions.sh.
#
# Given a branch name and a repo root, the script must install the PHP core
# extensions listed one per line in extra-plugins/<branch>/php-extensions
# (comments and blank lines ignored) via docker-php-ext-install, and do
# nothing (exit 0) when there is no such file. docker-php-ext-install is
# replaced with a stub on PATH that records its arguments.
#
# Usage: tests/build-scripts/test-install-extra-php-extensions.sh

set -u

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$REPO_ROOT/scripts/build-scripts/install-extra-php-extensions.sh"

PASS=0
FAIL=0
WORK=""

cleanup() { [ -n "$WORK" ] && rm -rf "$WORK"; }
trap cleanup EXIT

setup() {
    cleanup
    WORK="$(mktemp -d)"
    mkdir -p "$WORK/bin" "$WORK/root"
    cat > "$WORK/bin/docker-php-ext-install" <<STUB
#!/bin/sh
echo "\$*" >> "$WORK/calls"
STUB
    chmod +x "$WORK/bin/docker-php-ext-install"
    : > "$WORK/calls"
}

run_script() {
    PATH="$WORK/bin:$PATH" "$SCRIPT" "$@" >"$WORK/stdout" 2>"$WORK/stderr"
    echo $?
}

check() {
    local name="$1" ok="$2"
    if [ "$ok" = "0" ]; then
        PASS=$((PASS + 1)); echo "PASS: $name"
    else
        FAIL=$((FAIL + 1)); echo "FAIL: $name"
        [ -s "$WORK/stderr" ] && sed 's/^/      stderr: /' "$WORK/stderr"
        [ -s "$WORK/calls" ] && sed 's/^/      calls:  /' "$WORK/calls"
    fi
}

# Sorted, space-separated set of every extension name passed to the stub.
installed() { tr ' ' '\n' < "$WORK/calls" | grep -v '^$' | sort | tr '\n' ' ' | sed 's/ $//'; }

# --- 1. listed extensions are installed ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
printf 'pdo_mysql\n' > "$WORK/root/extra-plugins/dev/php-extensions"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ "$(installed)" = "pdo_mysql" ] && ok=0
check "dev branch installs the listed extension" "$ok"

# --- 2. several extensions, comments and blank lines ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
printf '# needed by prime mover\npdo_mysql\n\n  bcmath  \n# trailing comment\n' > "$WORK/root/extra-plugins/dev/php-extensions"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ "$(installed)" = "bcmath pdo_mysql" ] && ok=0
check "comments and blank lines are ignored, all extensions installed" "$ok"

# --- 3. other branch with no list: nothing installed ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
printf 'pdo_mysql\n' > "$WORK/root/extra-plugins/dev/php-extensions"
rc=$(run_script production "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "production branch installs nothing" "$ok"

# --- 4. slashed branch name never resolves to a directory ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
printf 'pdo_mysql\n' > "$WORK/root/extra-plugins/dev/php-extensions"
rc=$(run_script "feature/../dev" "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "slashed branch name installs nothing" "$ok"

# --- 5. extras directory without a list file: no-op ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "extras directory without php-extensions is a no-op" "$ok"

# --- 6. list file with only comments: no install call at all ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
printf '# nothing yet\n\n' > "$WORK/root/extra-plugins/dev/php-extensions"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "empty list is a no-op" "$ok"

# --- 7. installer failure propagates ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
printf 'pdo_mysql\n' > "$WORK/root/extra-plugins/dev/php-extensions"
printf '#!/bin/sh\nexit 9\n' > "$WORK/bin/docker-php-ext-install"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" != "0" ] && ok=0
check "installer failure fails the script" "$ok"

echo
echo "Passed: $PASS  Failed: $FAIL"
[ "$FAIL" -eq 0 ]
