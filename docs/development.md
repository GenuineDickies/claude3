# Development Reference

Last audited: 2026-03-08

This file is the entry point for the project documentation set. It summarizes the current codebase shape and points to the deeper references that were verified against the application source.

## Current Application Scope

The codebase supports more than basic dispatch. Verified modules currently include:

- Service request intake, rapid dispatch, lifecycle updates, photos, signatures, warranties, and evidence packages
- Customer management, notification preferences, SMS correspondence, and message templates
- Public customer workflows for location sharing, estimate approval, signature capture, and change-order approval
- Estimates, work orders, invoices, receipts, payments, and change orders
- Accounting, chart of accounts, journal entries, financial reports, expenses, vendors, and vendor documents
- Document inbox processing, AI-assisted document categorization, and transaction imports
- Technician compliance tracking and API health monitoring
- RBAC-driven page access, user administration, page registry sync, and audit logging

## Documentation Set

- [architecture.md](architecture.md): code-aligned architecture, data flow, and diagrams
- [schema-reference.md](schema-reference.md): grouped table reference derived from migrations and model relationships
- [access-control.md](access-control.md): RBAC model, protected page registry, and page assignment flow
- [admin-guide.md](admin-guide.md): administrator tasks for users, roles, pages, settings, and operational controls
- [user-guide.md](user-guide.md): dispatcher and operator workflows
- [configuration.md](configuration.md): `.env`, database, queue, session, security, and third-party integrations
- [api-reference.md](api-reference.md): public APIs, authenticated AJAX endpoints, and behavior notes
- [documentation-audit-report.md](documentation-audit-report.md): structured audit results, discrepancies, and statuses

## Verified Runtime Characteristics

- Protected application pages live inside the `auth`, `active-user`, and `page-access` middleware group in `routes/web.php`.
- Public customer actions are token-driven: `/locate/{token}`, `/sign/{token}`, `/change-orders/{token}`, and `/estimates/approve/{token}`.
- Telnyx webhook verification uses the configured ED25519 public key and a configurable webhook tolerance.
- Location reverse geocoding is a best-effort background enrichment step and must not break a successful GPS submission.
- Default local queue and session storage use the database drivers defined in `.env.example`.

## Where To Start

- New developers: read [configuration.md](configuration.md), then [user-guide.md](user-guide.md)
- Admins and operators: read [admin-guide.md](admin-guide.md) and [access-control.md](access-control.md)
- Integrators and deployers: read [api-reference.md](api-reference.md) and the deployment section in [../README.md](../README.md)
- Maintainers auditing behavior: start with [architecture.md](architecture.md) and [documentation-audit-report.md](documentation-audit-report.md)
