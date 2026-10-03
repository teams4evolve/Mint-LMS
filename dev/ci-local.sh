#!/usr/bin/env bash
# Local checks (same idea as GitHub CI, lighter). Prints file + line on failure.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

failed=0

echo "=== PHP syntax ==="
while IFS= read -r -d '' file || [ -n "${file:-}" ]; do
	[ -z "${file:-}" ] && continue
	if ! php -l "$file"; then
		failed=1
	fi
done < <(find src views templates mint-lms.php -type f -name '*.php' -print0)

if [ ! -x vendor/bin/phpcs ]; then
	echo "=== WordPress coding standards ==="
	echo "vendor/bin/phpcs missing. Run: composer install"
	exit 1
fi

echo "=== WordPress coding standards (errors only) ==="
if ! vendor/bin/phpcs --runtime-set ignore_warnings_on_exit 1 -p --report=full; then
	failed=1
fi

if [ "$failed" -ne 0 ]; then
	echo
	echo "Local checks FAILED. Fix the file/line above, then commit and push again."
	echo "To push anyway: git push --no-verify"
	exit 1
fi

echo
echo "Local checks passed."
