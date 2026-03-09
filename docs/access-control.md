# Role-Based Page Access

This application uses centralized role-based page access. Users can hold multiple roles, roles map to registered pages, and every protected page checks access through one shared function.

## Core behavior

- The `Administrator` role always has access to every registered page.
- The application prevents removing or disabling the last active administrator.
- Protected pages run through `requirePageAccess($pagePath)` by way of the shared `page-access` middleware.
- Disabled users cannot authenticate and active disabled sessions are forced out by middleware.

## Admin workflow

1. Open `Administration -> Users` to create users, update usernames or email addresses, assign multiple roles, and enable or disable accounts.
2. Open `Administration -> Roles` to create business roles such as Dispatcher, Accounting, or Reports.
3. Open `Administration -> Pages` to sync the route registry or register special pages manually.
4. Open `Roles -> Access` for a role to check the pages that role may open.

## Adding a new protected page

1. Add the new route inside the authenticated web route group in [routes/web.php](routes/web.php).
2. Keep the route inside the `auth`, `active-user`, and `page-access` middleware stack. That stack is already applied to the main protected group.
3. Sync the page registry from the admin UI on [docs/access-control.md](docs/access-control.md), or run the same flow during seeding with `AccessControlSeeder`.
4. Assign the new page to one or more roles from the role access editor.

## How the page registry works

- Registered pages live in the `pages` table.
- Page sync discovers protected routes and stores a canonical page path such as `/dashboard`, `/customers`, or `/admin/users`.
- Form actions and nested endpoints resolve back to their canonical page path through `PageAccessResolver`, so access logic stays centralized instead of being duplicated in controllers.

## Managing roles and users

1. Create the role first.
2. Sync or register the page.
3. Assign pages to the role.
4. Edit the user and attach one or more roles.

## Bootstrap administrator

- The seeded user `test@example.com` is assigned the `Administrator` role automatically.
- `UserFactory` also attaches the administrator role by default in tests so existing authenticated feature tests keep working unless they explicitly detach the role.

## Security notes

- Authentication continues to use Laravel’s hashed password column.
- Login attempts, successful logins, denied page access, user changes, role changes, and page changes are written to `audit_logs`.
- CSRF protection remains active on all web forms.