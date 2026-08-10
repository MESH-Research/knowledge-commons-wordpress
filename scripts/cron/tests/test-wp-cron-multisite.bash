#!/bin/bash
#
# Behavioural tests for wp-cron-multisite.bash.
#
# These exercise WHAT the runner must guarantee, not HOW it is implemented:
#   1. Every site returned by `wp site list` has its cron processed.
#   2. A failure processing one site does not abort the others (fault tolerance).
#   3. A per-site failure does not fail the whole run (exit 0), because one
#      broken site must never stop cron for the rest of the network.
#   4. If the site list itself cannot be retrieved, the run fails loudly (exit != 0).
#
# The `wp` binary is stubbed on PATH so nothing real is invoked and each test is
# fully isolated. The stub records every `cron event run --url=<url>` it receives.

set -u

RUNNER="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/wp-cron-multisite.bash"
FAILURES=0

pass() { echo "PASS: $1"; }
fail() { echo "FAIL: $1"; FAILURES=$((FAILURES + 1)); }

# Build an isolated sandbox with a fake `wp` on PATH.
#   $1 = newline-separated site URLs the stub should report for `site list`
#   $2 = (optional) a URL for which `cron event run` should exit 1
make_sandbox() {
    local sites="$1" bad_url="${2:-}"
    SANDBOX="$(mktemp -d)"
    PROCESSED_LOG="$SANDBOX/processed.log"
    : > "$PROCESSED_LOG"
    printf '%s\n' "$sites" > "$SANDBOX/sites.txt"

    cat > "$SANDBOX/wp" <<STUB
#!/bin/bash
# Fake wp-cli. Understands just enough: 'site list' and 'cron event run'.
args="\$*"
if [[ "\$args" == *"site list"* ]]; then
    grep -v '^\$' "$SANDBOX/sites.txt"
    exit 0
fi
if [[ "\$args" == *"cron event run"* ]]; then
    url=""
    for a in "\$@"; do
        case "\$a" in --url=*) url="\${a#--url=}";; esac
    done
    echo "\$url" >> "$PROCESSED_LOG"
    if [ -n "$bad_url" ] && [ "\$url" = "$bad_url" ]; then
        echo "boom" >&2
        exit 1
    fi
    exit 0
fi
exit 0
STUB
    chmod +x "$SANDBOX/wp"
}

cleanup_sandbox() { rm -rf "$SANDBOX"; }

run_runner() {
    PATH="$SANDBOX:$PATH" WP_PATH="/does/not/matter" RUN_CRON="true" \
        bash "$RUNNER" >"$SANDBOX/out.log" 2>&1
    echo $?
}

processed_count() { grep -c . "$PROCESSED_LOG"; }
was_processed() { grep -qxF "$1" "$PROCESSED_LOG"; }

# ---------------------------------------------------------------------------
# Test 1: every site is processed exactly once
# ---------------------------------------------------------------------------
make_sandbox $'https://a.test\nhttps://b.test\nhttps://c.test'
rc="$(run_runner)"
if [ "$(processed_count)" -eq 3 ] && was_processed "https://a.test" \
    && was_processed "https://b.test" && was_processed "https://c.test"; then
    pass "processes every site returned by 'wp site list'"
else
    fail "processes every site (got $(processed_count) of 3): $(cat "$PROCESSED_LOG" | tr '\n' ' ')"
fi
cleanup_sandbox

# ---------------------------------------------------------------------------
# Test 2 & 3: a failing site does not abort the rest, and run still exits 0
# ---------------------------------------------------------------------------
make_sandbox $'https://a.test\nhttps://bad.test\nhttps://c.test' "https://bad.test"
rc="$(run_runner)"
if was_processed "https://a.test" && was_processed "https://c.test"; then
    pass "continues past a failing site to process the remaining sites"
else
    fail "should process sites after a failing one: $(cat "$PROCESSED_LOG" | tr '\n' ' ')"
fi
if [ "$rc" -eq 0 ]; then
    pass "a per-site failure does not fail the whole run (exit 0)"
else
    fail "expected exit 0 on per-site failure, got $rc"
fi
cleanup_sandbox

# ---------------------------------------------------------------------------
# Test 4: unable to list sites -> loud failure (non-zero)
# ---------------------------------------------------------------------------
SANDBOX="$(mktemp -d)"
cat > "$SANDBOX/wp" <<'STUB'
#!/bin/bash
# site list fails; anything else would be a bug to even reach
[[ "$*" == *"site list"* ]] && { echo "db down" >&2; exit 1; }
exit 0
STUB
chmod +x "$SANDBOX/wp"
rc="$(PATH="$SANDBOX:$PATH" WP_PATH="/x" RUN_CRON="true" bash "$RUNNER" >/dev/null 2>&1; echo $?)"
if [ "$rc" -ne 0 ]; then
    pass "fails loudly when the site list cannot be retrieved"
else
    fail "expected non-zero exit when 'wp site list' fails, got $rc"
fi
rm -rf "$SANDBOX"

# ---------------------------------------------------------------------------
echo "-------------------------------------------"
if [ "$FAILURES" -eq 0 ]; then
    echo "ALL TESTS PASSED"
    exit 0
else
    echo "$FAILURES TEST(S) FAILED"
    exit 1
fi
