# Admin Guide

Last audited: 2026-03-08

This guide covers the administrator-facing workflows that are implemented in the current codebase.

Related reference:

- See `docs/workflow-context.md` for the actor-channel-stage decision framework that should be applied to consent, approvals, payments, evidence capture, and other workflow-sensitive changes.

## 1. Accessing Administration

Prerequisites:

- You must be authenticated.
- Your user must be active.
- Your assigned roles must include access to the relevant admin pages, unless you hold the reserved `Administrator` role.

Primary admin areas:

- `Administration -> Users`
- `Administration -> Roles`
- `Administration -> Pages`
- `Roles -> Access`
- `Settings`
- `Settings -> API Monitor`

## 2. User Management

Route anchors:

- `GET /admin/users`
- `GET /admin/users/create`
- `GET /admin/users/{user}/edit`
- `POST /admin/users/{user}/toggle-status`

### Create a user

1. Open `Administration -> Users`.
2. Choose `Create user`.
3. Enter name, username, email, password, confirmation, and status.
4. Assign one or more roles.
5. Save the user.

Behavior verified in code:

- Passwords are hashed before storage.
- Roles are synced immediately on create.
- Creation is audit-logged as `user_created`.

### Edit a user

1. Open the user edit screen.
2. Update identity, status, and assigned roles.
3. Leave the password blank to keep the existing password.
4. Save changes.

Safety rules:

- The last active administrator cannot lose the `Administrator` role.
- The last active administrator cannot be disabled.
- Changes are audit-logged as `user_updated`.

### Disable or re-enable a user

1. Use the status toggle action from the users screen.
2. Confirm the user is not the last active administrator.

Runtime effect:

- Disabled users cannot log in.
- Already-authenticated disabled users are logged out by middleware on their next request.

## 3. Role Management

Route anchors:

- `GET /admin/roles`
- `POST /admin/roles`
- `PUT /admin/roles/{role}`
- `DELETE /admin/roles/{role}`

### Create a role

1. Open `Administration -> Roles`.
2. Select `New Role`.
3. Enter `role_name` and optional description.
4. Save.

### Edit a role

1. Open the roles index.
2. Choose `Edit` for the role.
3. Update the name or description.
4. Save.

### Delete a role

Deletion is blocked when:

- The role is `Administrator`.
- The role is still assigned to any user.

When deletion is allowed, related page assignments are detached before the role is removed.

## 4. Page Registry Management

Route anchors:

- `GET /admin/pages`
- `POST /admin/pages`
- `PUT /admin/pages/{page}`
- `DELETE /admin/pages/{page}`
- `POST /admin/pages/sync`

### Sync registered pages from routes

1. Open `Administration -> Pages`.
2. Use `Sync`.
3. Review any newly discovered canonical paths.

What sync does:

- Scans protected GET routes.
- Normalizes them to canonical page paths through `PageAccessResolver`.
- Creates missing `pages` records and fills missing labels.

### Register or edit a page manually

Use this when a protected route needs a clearer label or when a special page path should exist even if it is not discovered automatically.

## 5. Assigning Page Access To Roles

Route anchors:

- `GET /admin/access/roles/{role}`
- `PUT /admin/access/roles/{role}`

Workflow:

1. Open `Administration -> Roles`.
2. Choose `Access` on the target role.
3. Filter the page list if needed.
4. Check the pages the role may access.
5. Save.

Important behavior:

- The `Administrator` role always has access to all pages and cannot be restricted.
- Assignments are written to the `page_role` pivot and audit-logged as `role_access_updated`.

## 6. Settings Management

Route anchors:

- `GET /settings`
- `PUT /settings`
- `PUT /settings/{key}`
- `GET /settings/tax-rates`
- `PUT /settings/tax-rates`
- `PUT /settings/approval-mode`

Current settings groups defined in code:

- `General`
- `Google Maps`
- `Telnyx SMS`
- `Advanced`

Notable settings:

- Company branding fields used in receipts, public pages, and SMS templates
- `location_link_expiry_hours`
- `compliance_tracking_enabled`
- Estimate approval mode and threshold
- Google Maps API key
- Telnyx API/public key, from number, messaging profile ID
- Optional alternate location base URL

Encrypted-at-rest settings:

- `google_maps_api_key`
- `telnyx_api_key`

## 7. API Monitor Management

Route anchors:

- `GET /settings/api-monitor`
- `POST /settings/api-monitor`
- `PUT /settings/api-monitor/{endpoint}`
- `POST /settings/api-monitor/{endpoint}/run`

Use this area to maintain external endpoint health checks surfaced on the dashboard.

## 8. SMS Consent Workflow

Current operational rule:

- Customer SMS consent is recorded by staff based on verbal consent during intake.
- Technicians must grant their own SMS consent while signed in to their own account.
- The application should reuse an existing phone number when possible instead of asking for it again.

Customer workflow:

1. During service-request intake, confirm verbal SMS consent with the customer.
2. Record that consent using the intake workflow before sending location or status texts.
3. If verbal consent is not recorded, the system blocks customer SMS sends and tells staff to obtain consent first.

Technician workflow:

1. Assign the Technician role from `Administration -> Users`.
2. Have the technician sign in and open their own profile or technician compliance screen.
3. The technician enters their mobile number once if needed.
4. The technician grants SMS consent for dispatch/location texts on their own account.

Important behavior:

- Administrators can create the technician profile by role assignment, but they do not grant SMS consent on another person's behalf.
- The assigned technician must have both a mobile phone number and technician-recorded SMS consent before dispatch location texts can be sent.
- STOP and HELP keyword handling remains active for compliance on inbound SMS.

### Workflow decision matrix

Use this matrix when deciding how consent or approval should be collected:

| Actor | Channel | Workflow stage | Decision owner | Correct mechanism | Avoid |
| --- | --- | --- | --- | --- | --- |
| Customer | Phone call with staff | Intake | Customer | Verbal consent recorded by staff | Website or SMS self-consent flow |
| Customer | Public website | Website intake or form submission | Customer | Written/web consent in that form flow | Assuming verbal consent exists |
| Technician | Signed-in app account | Onboarding or profile completion | Technician | Self-service consent in own account | Admin granting consent for them |
| Staff/Admin | Internal app | Dispatch or status updates | Staff is operator, not consent owner | Use already-recorded consent only | Recording a new third-party consent without the owner present |

Decision rule:

1. Identify who is acting.
2. Identify the current interaction channel.
3. Identify when in the workflow the action happens.
4. Match the consent or approval mechanism to that exact context instead of defaulting to a generic provider pattern.

## 9. Audit Logging

The following admin actions are explicitly audit-logged:

- User creation, update, and status toggle
- Role creation, update, and deletion
- Page creation, update, deletion, and sync
- Role page-access updates
- Page access denials and disabled-user session blocks

## 10. Operational Checklist For New Protected Pages

1. Add the route inside the authenticated middleware group.
2. Sync the page registry.
3. Verify the page appears under `Administration -> Pages`.
4. Assign the page to the correct role.
5. Test with a non-administrator user.