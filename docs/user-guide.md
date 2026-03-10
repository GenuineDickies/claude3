# User Guide

Last audited: 2026-03-08

This guide is written for dispatchers and internal operators who use the application day to day.

## 1. Authentication

Implemented routes:

- Register
- Login
- Forgot password
- Reset password
- Email verification prompt and resend
- Confirm password
- Logout

Notes:

- Registration is enabled in the current Breeze route set.
- Disabled accounts cannot continue using the application, even if they were already signed in.

## 2. Dashboard

`/dashboard` shows:

- Open service request count
- Requests created today
- Active customer count
- Recent service requests
- API health summary
- Compliance summary when `compliance_tracking_enabled` is enabled

## 3. Creating A Service Request

Primary path:

1. Open `New Ticket`.
2. Search for an existing customer or enter new customer details.
3. Provide service type, vehicle information, and any pricing notes.
4. Save the service request.
5. Review the service request detail page for messaging, estimates, and next actions.

Alternate path:

1. Open `Rapid Dispatch`.
2. Enter the abbreviated intake details.
3. Use the parser-assisted workflow to create the request more quickly.

## 4. Messaging A Customer

Supported messaging flows:

- Free-text outbound SMS from the service request view
- Template-based outbound SMS from the service request view
- Auto-logged inbound SMS via Telnyx webhook
- Unified correspondence timeline entries

Important behavior:

- Non-compliance templates require active SMS consent.
- Compliance messages such as opt-in and help replies can bypass the normal consent gate.

## 5. Requesting Customer Location

Dispatcher flow:

1. Open the service request.
2. Trigger `Request location`.
3. If the customer lacks SMS consent, the system sends the welcome or opt-in message instead.
4. Once consent exists, the app sends a location link.

Customer flow:

1. The customer opens `/locate/{token}` from the SMS link.
2. The page asks for browser geolocation permission.
3. The browser posts coordinates to `/api/locate/{token}`.
4. The app stores coordinates immediately and then attempts reverse geocoding in the background.

Operational note:

- The location submission is successful even if reverse geocoding later fails.

## 6. Estimates, Work Orders, And Billing

Current implemented workflow:

1. Create an estimate from the service request.
2. Add line items from the catalog.
3. Request customer approval when required.
4. Convert approved work into a work order.
5. Create invoices from completed work orders.
6. Record payments and issue receipts.
7. Revise estimates or invoices through the versioning flows when needed.

Public customer routes involved:

- `/estimates/approve/{token}`
- `/change-orders/{token}`
- `/sign/{token}`

## 7. Evidence And Completion Artifacts

A service request can accumulate:

- Photos
- Signatures
- Payment records
- Correspondence
- Warranty records
- Documents

The evidence package view consolidates these artifacts for review.

## 8. Documents And Inbox Processing

Operators can:

- Upload generic documents
- Use the document inbox for bulk uploads
- Link or re-match documents to records
- Accept or reject suggested matches
- Review AI-assisted categorization and transaction imports

## 9. Reports, Expenses, Vendors, And Compliance

Additional internal modules available by role:

- Reports dashboard and financial dashboard
- Expense management
- Vendor management and vendor documents
- Technician compliance profiles
- Warranty management

Access to these areas depends on page assignments for the user’s roles.