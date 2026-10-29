#!/usr/bin/env bash
#
# Guard: the PHP version baked into Dockerfile.php's base stage must satisfy
# the "php" requirement in the root composer.json. The shared base image is
# built from this Dockerfile, so if the two drift apart composer install fails
# on every branch whose composer.json is stricter than the image.
#
# Usage: tests/build-scripts/test-php-version-alignment.sh

set -u
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

python3 - "$REPO_ROOT" <<'PY'
import json, re, sys
root = sys.argv[1]

dockerfile = open(f"{root}/Dockerfile.php").read()
m = re.search(r"^FROM php:(\d+)\.(\d+)\.(\d+)[^\s]*\s+AS base", dockerfile, re.M)
if not m:
    print("FAIL: no 'FROM php:<x.y.z>... AS base' line in Dockerfile.php"); sys.exit(1)
image = tuple(int(x) for x in m.groups())

req = json.load(open(f"{root}/composer.json"))["require"]["php"].strip()

def parse(v):
    parts = [int(x) for x in v.split(".")]
    return tuple(parts + [0] * (3 - len(parts)))

def satisfies(constraint, ver):
    # Supports the forms used in this repo: ^x.y.z, ^x.y, ~x.y.z, >=x.y.z, exact.
    if constraint.startswith("^"):
        lo = parse(constraint[1:]); hi = (lo[0] + 1, 0, 0)
        return lo <= ver < hi
    if constraint.startswith("~"):
        lo = parse(constraint[1:]); n = constraint[1:].count(".")
        hi = (lo[0] + 1, 0, 0) if n == 0 else (lo[0], lo[1] + 1, 0)
        return lo <= ver < hi
    if constraint.startswith(">="):
        return ver >= parse(constraint[2:])
    return ver == parse(constraint)

ok = satisfies(req, image)
label = "PASS" if ok else "FAIL"
print(f"{label}: Dockerfile.php base PHP {'.'.join(map(str, image))} vs composer.json php {req}")
sys.exit(0 if ok else 1)
PY
