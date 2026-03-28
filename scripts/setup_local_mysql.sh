#!/bin/bash
# Local dev setup: create MySQL database and app user
set -euo pipefail

DB_NAME="claude3"
DB_USER="claude3_app"
DB_HOST="127.0.0.1"
DB_PASS="${DB_PASS:-}"
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
MYSQL_ADMIN_PASSWORD="${MYSQL_ADMIN_PASSWORD:-}"

if [[ -z "$DB_PASS" ]]; then
	echo "ERROR: Set DB_PASS in your shell before running this script." >&2
	echo "Example: DB_PASS='your-strong-password' bash scripts/setup_local_mysql.sh" >&2
	exit 1
fi

MYSQL_CMD=(mysql -u "$MYSQL_ADMIN_USER")
if [[ -n "$MYSQL_ADMIN_PASSWORD" ]]; then
	MYSQL_CMD+=("-p$MYSQL_ADMIN_PASSWORD")
fi

"${MYSQL_CMD[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"${MYSQL_CMD[@]}" -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';"
"${MYSQL_CMD[@]}" -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';"
"${MYSQL_CMD[@]}" -e "FLUSH PRIVILEGES;"
echo "DB_SETUP_OK: database '${DB_NAME}', user '${DB_USER}'@'${DB_HOST}' ready."
