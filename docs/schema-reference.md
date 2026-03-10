# Schema Reference

Last audited: 2026-03-08

This reference groups the current schema by business domain. It is derived from the migration inventory in `database/migrations/` and validated against the current Eloquent models.

## Core Identity And Access

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `users` | Internal authenticated users | `name`, `username`, `email`, `password`, `status` | many-to-many with `roles`; one-to-one with `technician_profiles` | `0001_01_01_000000_create_users_table.php` |
| `roles` | Business roles such as Administrator or Dispatcher | `role_name`, `description` | many-to-many with `users`; many-to-many with `pages` | `2026_03_08_130000_add_role_based_page_access_tables.php` |
| `pages` | Canonical protected page paths used by RBAC | `page_name`, `page_path`, `description` | many-to-many with `roles` | `2026_03_08_130000_add_role_based_page_access_tables.php` |
| `role_user` | User-to-role pivot | `user_id`, `role_id` | joins `users` and `roles` | `2026_03_08_130000_add_role_based_page_access_tables.php` |
| `page_role` | Role-to-page pivot | `page_id`, `role_id` | joins `roles` and `pages` | `2026_03_08_130000_add_role_based_page_access_tables.php` |
| `audit_logs` | Access and admin action logging | actor, event, metadata | written by access/admin flows | `2026_03_08_130000_add_role_based_page_access_tables.php` |

## Dispatch And Customer Operations

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `customers` | Customer master records | `first_name`, `last_name`, `phone`, `is_active`, consent fields | one-to-many with `service_requests`, `messages`, `correspondences`, `vehicles` | `2026_02_21_091941_create_customers_table.php` plus consent/preference migrations |
| `vehicles` | Reusable vehicle records | `customer_id`, `year`, `make`, `model` | belongs to `customers`; referenced by `service_requests` | `2026_02_21_200001_create_vehicles_table.php` |
| `service_requests` | Central dispatch/job record | customer, vehicle, service/catalog fields, status, location, token fields | belongs to `customers` and optional `vehicles`; parent for estimates, work orders, invoices, receipts, payments, photos, logs, warranties | `2026_02_21_171339_create_service_requests_table.php` plus alter migrations |
| `service_request_status_logs` | Status transition audit | `service_request_id`, `from_status`, `to_status` | belongs to `service_requests` | `2025_06_24_000000_create_service_request_status_logs_table.php` |
| `service_logs` | Manual and automatic operational entries | `service_request_id`, event metadata | belongs to `service_requests` | `2025_06_25_000003_create_service_logs_table.php` |
| `service_photos` | Stored evidence and scene photos | service request id, storage path, metadata | belongs to `service_requests` | `2025_06_25_000001_create_service_photos_table.php` |
| `service_signatures` | Captured customer signatures | service request id, token, signed payload | belongs to `service_requests` | `2025_06_25_000002_create_service_signatures_table.php` and follow-up nullable migration |
| `warranties` | Warranty records tied to jobs | service request id and warranty metadata | belongs to `service_requests` | `2025_06_25_000005_create_warranties_table.php` |

## Messaging And Correspondence

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `messages` | Raw inbound and outbound SMS log | `customer_id`, `service_request_id`, `direction`, `body`, `telnyx_message_id`, `status` | belongs to `customers` and optional `service_requests` | `2026_02_21_171339_create_messages_table.php` |
| `message_templates` | Reusable SMS templates | `slug`, `name`, `category`, `body` | referenced by outbound and compliance flows | `2026_02_21_230000_create_message_templates_table.php` |
| `correspondences` | Unified communication timeline | channel, direction, subject, body, customer/service request linkage | belongs to `customers`, optional `service_requests` | `2026_02_27_080000_create_correspondences_table.php` |

## Estimates, Work Orders, Billing, And Payments

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `estimates` | Customer quotes and approval workflows | totals, state/tax, status, approval token/version fields | belongs to `service_requests`; has many `estimate_items` | `2026_02_24_100000_create_estimates_table.php`, approval and versioning migrations |
| `estimate_items` | Estimate line items | qty, pricing, snapshot data | belongs to `estimates`; optionally tied to catalog items | `2026_02_24_100000_create_estimates_table.php` |
| `work_orders` | Approved work execution records | status, totals, snapshot data | belongs to `service_requests`; parent for invoices and change orders | `2026_02_25_100000_create_work_orders_table.php` |
| `change_orders` | Scope changes requiring customer approval | status, token, financial delta | belongs to `work_orders` and `service_requests` | `2026_02_26_110000_create_change_orders_tables.php` |
| `invoices` | Customer invoices and revisions | invoice number, totals, status, version fields | belongs to `service_requests`; references `work_orders`; parent for receipts | `2025_07_19_000001_create_invoices_table.php`, versioning migration |
| `receipts` | Receipts issued from billing/payment flows | receipt number and snapshot data | belongs to `service_requests`; optional invoice/payment links | `2025_06_24_000001_create_receipts_table.php` plus payment-link migration |
| `payment_records` | Recorded customer payments | amount, method, collected timestamp, references | belongs to `service_requests`; may link to invoices | `2025_06_25_000004_create_payment_records_table.php` |

## Catalog And Tax Configuration

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `catalog_categories` | Top-level catalog grouping | `name`, `type`, `is_active` | has many `catalog_items` | `2026_02_23_100000_create_catalog_categories_table.php` |
| `catalog_items` | Services and parts used in dispatch, estimates, and vendor docs | pricing, unit, active flags, account mapping | belongs to categories; referenced by estimates and vendor docs | `2026_02_23_100001_create_catalog_items_table.php` plus simplification/consolidation migrations |
| `service_types` | Legacy service type structure retained in migration history | service metadata | partially superseded by catalog-backed workflows | `2026_02_21_200000_create_service_types_table.php` and consolidation migration |
| `state_tax_rates` | State tax lookup for estimates | `state_code`, `state_name`, `tax_rate` | used by estimate pricing | `2026_02_24_100001_create_state_tax_rates_table.php` |

## Finance And Accounting

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `accounts` | Chart of accounts | `code`, `scope`, `category`, `name`, `type`, `parent_account_id`, `is_active` | self-parenting tree; referenced by journal lines, catalog items, vendor docs | `2026_02_26_300000_create_accounting_tables.php`, scope and chart migrations |
| `journal_entries` | GL entry headers | `entry_number`, `entry_date`, `source_type`, `source_id`, `status` | has many `journal_lines`; morphs to operational source documents | `2026_02_26_300000_create_accounting_tables.php` |
| `journal_lines` | Debit/credit lines | `journal_entry_id`, `account_id`, `debit`, `credit`, `description` | belongs to `journal_entries` and `accounts` | `2026_02_26_300000_create_accounting_tables.php` |
| `expenses` | Standalone business expenses | `expense_number`, `date`, `vendor`, `category`, `amount`, `payment_method` | belongs to creator; can have documents; may generate accounting entries | `2026_02_26_200000_create_expenses_table.php` |
| `document_accounting_links` | Cross-reference between source documents and GL output | document identifiers and journal ids | links invoices, expenses, vendor docs, and payments to journal entries | `2026_03_04_060000_accounting_engine_and_vendor_documents.php` |

## Vendors And Vendor Documents

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `vendors` | Supplier master records | name, contact info, default expense account linkage, active state | parent for `vendor_documents` | `2026_03_04_060000_accounting_engine_and_vendor_documents.php` |
| `vendor_documents` | Vendor receipts and invoices | `vendor_id`, `document_type`, totals, paid/posting status, notes | belongs to `vendors`; has many lines and attachments | `2026_03_04_060000_accounting_engine_and_vendor_documents.php` |
| `vendor_document_lines` | Vendor document line items | line type, part, qty, cost, account fields | belongs to `vendor_documents`; optional catalog/account links | `2026_03_04_060000_accounting_engine_and_vendor_documents.php` |
| `vendor_document_attachments` | Uploaded files for vendor documents | storage path, mime, original filename, size | belongs to `vendor_documents` | `2026_03_04_060000_accounting_engine_and_vendor_documents.php` |

## Documents, AI, And Transaction Imports

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `documents` | Generic uploaded documents with AI and match metadata | polymorphic owner, category, AI summary/tags/status, match status | morph-to owner; has many `document_line_items` and `document_transaction_imports` | `2025_06_25_000006_create_documents_table.php`, AI/match migrations |
| `document_line_items` | AI-extracted draft line items from receipts/invoices | description, qty, amount, category, status, raw data | belongs to `documents` | `2026_03_04_034003_create_document_line_items_table.php` |
| `document_transaction_imports` | AI-parsed spreadsheet transactions awaiting review | date, description, amount, type, category, account code, status | belongs to `documents`; optional created expense and journal entry links | `2026_02_28_103435_create_document_transaction_imports_table.php` |

## Monitoring, Settings, And Infrastructure

| Table | Purpose | Key fields | Relationships | Migration anchors |
|---|---|---|---|---|
| `settings` | Cached application configuration with optional encryption | `group`, `key`, `value`, `is_encrypted` | read across branding, Telnyx, Google Maps, and advanced settings flows | `2026_02_22_120000_create_settings_table.php` |
| `api_monitor_endpoints` | External endpoint health checks | URL, active state, thresholds, last status | parent for monitor runs and dashboard summary | `2026_02_26_100000_create_api_monitor_tables.php` |
| `api_monitor_runs` | Historical endpoint check results | endpoint id, status, response metadata | belongs to `api_monitor_endpoints` | `2026_02_26_100000_create_api_monitor_tables.php` |
| `jobs` | Database-backed queue jobs | payload, attempts, reservation fields | used by the default queue driver | `0001_01_01_000002_create_jobs_table.php` |
| `cache` and `cache_locks` | Database-backed cache and locks | cache keys, values, expirations | framework infrastructure | `0001_01_01_000001_create_cache_table.php` |

## Notes

- The migration history includes legacy and transitional structures such as `service_types`; the current UI often prefers catalog-backed services.
- Public customer token workflows rely on fields stored directly on operational tables instead of dedicated token tables.
- For exact column definitions, the migration files remain the source of truth.