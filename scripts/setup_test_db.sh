#!/usr/bin/env bash
set -euo pipefail

# Provision MySQL test schema and grant rights for local test runs.
# Run with a user that can manage MySQL grants (typically root).

TEST_DB_NAME="claude3_test"
APP_DB_USER="claude3_app"
APP_DB_HOST="127.0.0.1"
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
MYSQL_ADMIN_PASSWORD="${MYSQL_ADMIN_PASSWORD:-}"

MYSQL_CMD=(mysql -u "$MYSQL_ADMIN_USER")
if [[ -n "$MYSQL_ADMIN_PASSWORD" ]]; then
	MYSQL_CMD+=("-p$MYSQL_ADMIN_PASSWORD")
fi

"${MYSQL_CMD[@]}" -e "CREATE DATABASE IF NOT EXISTS ${TEST_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"${MYSQL_CMD[@]}" -e "GRANT ALL PRIVILEGES ON ${TEST_DB_NAME}.* TO '${APP_DB_USER}'@'${APP_DB_HOST}';"
"${MYSQL_CMD[@]}" -e "FLUSH PRIVILEGES;"

echo "Test database ready: ${TEST_DB_NAME}"
echo "Granted: ${APP_DB_USER}@${APP_DB_HOST}"
