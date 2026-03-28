#!/bin/bash
set -euo pipefail

# Mark the orphaned table as already migrated
DB_NAME="${DB_NAME:-claude3}"
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
MYSQL_ADMIN_PASSWORD="${MYSQL_ADMIN_PASSWORD:-}"

MYSQL_CMD=(mysql -u "$MYSQL_ADMIN_USER")
if [[ -n "$MYSQL_ADMIN_PASSWORD" ]]; then
	MYSQL_CMD+=("-p$MYSQL_ADMIN_PASSWORD")
fi

"${MYSQL_CMD[@]}" -D "$DB_NAME" -e "INSERT IGNORE INTO migrations (migration, batch) VALUES ('2025_06_24_000000_create_service_request_status_logs_table', 2);"
echo "MARKED_OK"
