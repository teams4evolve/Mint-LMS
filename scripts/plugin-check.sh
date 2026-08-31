#!/usr/bin/env bash
# Run WordPress Plugin Check against a release tree without touching the dev plugin copy.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WP_PATH="${WP_PATH:-/var/www/html/wp_local}"
SOURCE_SLUG="mint-lms"
CHECK_SLUG="mint-lms-release-check"
BUILD_DIR="$(mktemp -d)"
TARGET="$WP_PATH/wp-content/plugins/$CHECK_SLUG"

cleanup() {
	rm -rf "$BUILD_DIR"
}
trap cleanup EXIT

if ! command -v wp >/dev/null 2>&1; then
	echo "wp-cli is required. Install WP-CLI and ensure wp is on PATH." >&2
	exit 1
fi

if ! wp core is-installed --path="$WP_PATH" >/dev/null 2>&1; then
	echo "WordPress must be installed at WP_PATH=$WP_PATH" >&2
	exit 1
fi

rsync -a "$PLUGIN_DIR/" "$BUILD_DIR/"

if [[ -f "$BUILD_DIR/composer.json" ]]; then
	(composer install --no-dev --optimize-autoloader --no-interaction --working-dir="$BUILD_DIR")
fi

RSYNC_EXCLUDES=()
while IFS= read -r line || [[ -n "$line" ]]; do
	line="${line//$'\r'/}"
	[[ -z "$line" || "$line" =~ ^[[:space:]]*# ]] && continue
	RSYNC_EXCLUDES+=( "--exclude=${line#/}" )
done < "$BUILD_DIR/.distignore"

RELEASE_DIR="$(mktemp -d)"
rsync -a "${RSYNC_EXCLUDES[@]}" "$BUILD_DIR/" "$RELEASE_DIR/"

rm -rf "$TARGET"
mkdir -p "$(dirname "$TARGET")"
cp -a "$RELEASE_DIR/." "$TARGET/"

if ! wp plugin is-installed plugin-check --path="$WP_PATH" >/dev/null 2>&1; then
	wp plugin install plugin-check --activate --path="$WP_PATH"
fi

wp plugin activate "$CHECK_SLUG" --path="$WP_PATH" >/dev/null 2>&1 || true
wp plugin check "$CHECK_SLUG" --path="$WP_PATH" --ignore-warnings

wp plugin deactivate "$CHECK_SLUG" --path="$WP_PATH" >/dev/null 2>&1 || true
if wp plugin is-installed "$SOURCE_SLUG" --path="$WP_PATH" >/dev/null 2>&1; then
	wp plugin activate "$SOURCE_SLUG" --path="$WP_PATH" >/dev/null 2>&1 || true
fi

echo "Plugin Check passed for release tree ($CHECK_SLUG)."
