#!/usr/bin/env bash
#
# Behavioural test for scripts/build-scripts/install-extra-plugins.sh.
#
# The script is given a branch name and a repo root. It must run
# `composer install` inside extra-plugins/<branch>/ when that directory holds
# a composer.json, and do nothing (exiting 0) otherwise. Composer itself is
# replaced with a stub on PATH that records where and how it was invoked.
#
# Usage: tests/build-scripts/test-install-extra-plugins.sh

set -u

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$REPO_ROOT/scripts/build-scripts/install-extra-plugins.sh"

PASS=0
FAIL=0
WORK=""

cleanup() { [ -n "$WORK" ] && rm -rf "$WORK"; }
trap cleanup EXIT

# Fresh fake repo root with a stubbed composer on PATH. The stub appends
# "<cwd> <args>" to $WORK/calls each time it runs.
setup() {
    cleanup
    WORK="$(mktemp -d)"
    mkdir -p "$WORK/bin" "$WORK/root"
    cat > "$WORK/bin/composer" <<STUB
#!/bin/sh
echo "\$(pwd) \$*" >> "$WORK/calls"
STUB
    chmod +x "$WORK/bin/composer"
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

# --- 1. branch with an extras project: composer install runs in that dir ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
echo '{}' > "$WORK/root/extra-plugins/dev/composer.json"
rc=$(run_script dev "$WORK/root")
calls=$(wc -l < "$WORK/calls")
first=$(head -1 "$WORK/calls")
ok=1
if [ "$rc" = "0" ] && [ "$calls" = "1" ] \
   && [ "${first%% *}" = "$WORK/root/extra-plugins/dev" ] \
   && [[ " $first " == *" install "* ]]; then ok=0; fi
check "dev branch installs extra-plugins/dev" "$ok"

# --- 2. same layout, different branch: nothing installed, exit 0 ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
echo '{}' > "$WORK/root/extra-plugins/dev/composer.json"
rc=$(run_script production "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "production branch installs nothing" "$ok"

# --- 3. branch name with a slash never escapes into another directory ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
echo '{}' > "$WORK/root/extra-plugins/dev/composer.json"
rc=$(run_script "feature/../dev" "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "slashed branch name installs nothing" "$ok"

# --- 4. no extra-plugins directory at all: exit 0 ---
setup
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "missing extra-plugins directory is a no-op" "$ok"

# --- 5. directory present but no composer.json: exit 0 ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" = "0" ] && [ ! -s "$WORK/calls" ] && ok=0
check "extras directory without composer.json is a no-op" "$ok"

# --- 6. composer failure propagates as a non-zero exit ---
setup
mkdir -p "$WORK/root/extra-plugins/dev"
echo '{}' > "$WORK/root/extra-plugins/dev/composer.json"
printf '#!/bin/sh\nexit 7\n' > "$WORK/bin/composer"
rc=$(run_script dev "$WORK/root")
ok=1; [ "$rc" != "0" ] && ok=0
check "composer failure fails the script" "$ok"

# --- 7. multiple extras projects: only the named branch's is installed ---
setup
mkdir -p "$WORK/root/extra-plugins/dev" "$WORK/root/extra-plugins/staging"
echo '{}' > "$WORK/root/extra-plugins/dev/composer.json"
echo '{}' > "$WORK/root/extra-plugins/staging/composer.json"
rc=$(run_script staging "$WORK/root")
calls=$(wc -l < "$WORK/calls")
first=$(head -1 "$WORK/calls")
ok=1
if [ "$rc" = "0" ] && [ "$calls" = "1" ] \
   && [ "${first%% *}" = "$WORK/root/extra-plugins/staging" ]; then ok=0; fi
check "staging branch installs only extra-plugins/staging" "$ok"

echo
echo "Passed: $PASS  Failed: $FAIL"
[ "$FAIL" -eq 0 ]
