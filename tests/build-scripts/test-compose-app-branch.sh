#!/usr/bin/env bash
#
# Behavioural test: the local test compose file must pass an APP_BRANCH build
# argument to every service built from Dockerfile.php, defaulting to "dev" so
# local builds pick up extra-plugins/dev/, and honouring an APP_BRANCH
# environment override.
#
# Usage: tests/build-scripts/test-compose-app-branch.sh

set -u

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="$REPO_ROOT/docker-compose.test.yml"

PASS=0
FAIL=0

check() {
    local name="$1" ok="$2"
    if [ "$ok" = "0" ]; then PASS=$((PASS + 1)); echo "PASS: $name"
    else FAIL=$((FAIL + 1)); echo "FAIL: $name"; fi
}

# Services built from Dockerfile.php, derived from the file itself so the test
# does not hard-code service names.
php_services() {
    docker compose -f "$COMPOSE" config --format json 2>/dev/null \
      | python3 -c '
import json, sys
cfg = json.load(sys.stdin)
for name, svc in cfg.get("services", {}).items():
    if (svc.get("build") or {}).get("dockerfile") == "Dockerfile.php":
        print(name)'
}

branch_arg() {
    # $1 = service, remaining env is inherited
    docker compose -f "$COMPOSE" config --format json 2>/dev/null \
      | python3 -c '
import json, sys
cfg = json.load(sys.stdin)
print(((cfg["services"][sys.argv[1]].get("build") or {}).get("args") or {}).get("APP_BRANCH", ""))' "$1"
}

services=$(php_services)
ok=1; [ -n "$services" ] && ok=0
check "compose file has services built from Dockerfile.php" "$ok"

for svc in $services; do
    v=$(env -u APP_BRANCH bash -c "$(declare -f branch_arg); COMPOSE='$COMPOSE'; branch_arg $svc")
    ok=1; [ "$v" = "dev" ] && ok=0
    check "$svc defaults APP_BRANCH build arg to dev" "$ok"

    v=$(APP_BRANCH=production bash -c "$(declare -f branch_arg); COMPOSE='$COMPOSE'; branch_arg $svc")
    ok=1; [ "$v" = "production" ] && ok=0
    check "$svc honours APP_BRANCH override" "$ok"
done

echo
echo "Passed: $PASS  Failed: $FAIL"
[ "$FAIL" -eq 0 ]
