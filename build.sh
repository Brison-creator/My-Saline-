#!/usr/bin/env bash
#
# Package the MySaline theme into an installable WordPress ZIP.
#
# The resulting archive contains a single top-level folder named "mysaline"
# (the theme slug), exactly as WordPress expects for Appearance → Themes →
# Add New → Upload Theme.
#
# Usage:
#   ./build.sh                # builds dist/mysaline.zip
#   ./build.sh 1.2.0          # also stamps version into style.css & readme.txt
#
set -euo pipefail

THEME_SLUG="mysaline"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="${ROOT_DIR}/${THEME_SLUG}"
DIST_DIR="${ROOT_DIR}/dist"
BUILD_DIR="${DIST_DIR}/${THEME_SLUG}"
ZIP_PATH="${DIST_DIR}/${THEME_SLUG}.zip"

if [[ ! -d "${SRC_DIR}" ]]; then
	echo "Error: theme source not found at ${SRC_DIR}" >&2
	exit 1
fi

# Optional version stamp.
if [[ "${1:-}" != "" ]]; then
	VERSION="$1"
	echo "Stamping version ${VERSION}…"
	# style.css header.
	sed -i.bak -E "s/^(Version:[[:space:]]*).*/\1${VERSION}/" "${SRC_DIR}/style.css" && rm -f "${SRC_DIR}/style.css.bak"
	# functions.php constant.
	sed -i.bak -E "s/(define\( 'MYSALINE_VERSION', ')[^']*(' \);)/\1${VERSION}\2/" "${SRC_DIR}/functions.php" && rm -f "${SRC_DIR}/functions.php.bak"
	# readme.txt stable tag.
	sed -i.bak -E "s/^(Stable tag:[[:space:]]*).*/\1${VERSION}/" "${SRC_DIR}/readme.txt" && rm -f "${SRC_DIR}/readme.txt.bak"
fi

echo "Cleaning previous build…"
rm -rf "${BUILD_DIR}" "${ZIP_PATH}"
mkdir -p "${BUILD_DIR}"

echo "Copying theme files…"
# Copy everything except developer-only artifacts.
if command -v rsync >/dev/null 2>&1; then
	rsync -a \
		--exclude='.git' \
		--exclude='.github' \
		--exclude='node_modules' \
		--exclude='*.map' \
		--exclude='.DS_Store' \
		--exclude='Thumbs.db' \
		"${SRC_DIR}/" "${BUILD_DIR}/"
else
	cp -R "${SRC_DIR}/." "${BUILD_DIR}/"
	find "${BUILD_DIR}" -name '.DS_Store' -delete 2>/dev/null || true
fi

# Verify required theme files exist.
for required in style.css index.php functions.php; do
	if [[ ! -f "${BUILD_DIR}/${required}" ]]; then
		echo "Error: required file ${required} missing from build." >&2
		exit 1
	fi
done

echo "Creating ${ZIP_PATH}…"
( cd "${DIST_DIR}" && zip -rq "${ZIP_PATH}" "${THEME_SLUG}" )

# Clean the staging folder, keep only the zip.
rm -rf "${BUILD_DIR}"

echo ""
echo "✅ Built: ${ZIP_PATH}"
echo "   Install via WordPress → Appearance → Themes → Add New → Upload Theme."
