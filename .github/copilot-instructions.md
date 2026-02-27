# Copilot instructions (repo-local)

## Terminal
The workspace lives inside WSL (Ubuntu). **All terminal commands must be executed via WSL**, e.g.:
```
wsl -e bash -c "cd /var/www/html/claude3 && <command> 2>&1"
```
Never run `php`, `composer`, `npm`, or `artisan` directly from PowerShell — always wrap in `wsl -e bash -c`.

## MCP pathing
- For Taskmaster MCP tools that require `projectRoot`, always pass the workspace UNC path exactly:
  - `\\wsl.localhost\Ubuntu\var\www\html\claude3`
- Do not pass Linux-only paths (for example, `/var/www/html/claude3`) to MCP `projectRoot` parameters.
- Do not pass converted Windows-local variants (for example, `C:\var\www\html\claude3`) to MCP `projectRoot` parameters.
- If uncertain, reuse the path shown in VS Code workspace info rather than reconstructing it manually.

## Project
Internal roadside-assistance app.
Stack: **Laravel (PHP) + MySQL**. Deploy target: **Shared hosting (cPanel/FTP)**.

## Non-negotiables- Always use modern syntax and current best practices for every language/framework in the stack (e.g. Tailwind v4 `bg-gray-500/75` instead of deprecated `bg-opacity-*`; PHP 8.x features where supported; Laravel's latest recommended patterns). Avoid deprecated APIs, classes, or utility names.
- Follow security best practices: validate & sanitize all input, use parameterized queries, escape output, apply CSRF protection on web routes, set secure HTTP headers, and never trust client-side data alone.- Optimize for clarity (names, file structure, plain-English domain vocabulary).
- Prefer simple solutions; avoid clever abstractions.
- Keep diffs small; don’t refactor unrelated code.
- Always validate before calling work done: run `php artisan test` (or the closest available check) and report what you ran.
- Be explicit when the user must do something manual (portals, hosting, credentials, DNS, cPanel):
  - Start a section titled **User action required**.
  - List exact steps the user must perform (copy/paste commands where possible).
  - Include a quick “how to verify it worked” step.
  - Never assume the user already did a manual step; ask or clearly state what’s needed.

## Secrets & sensitive data
- Never commit secrets. Use env vars; document names only in `.env.example`. Ensure `.env` is gitignored.
- Don’t log secrets/PII. Assume PDFs and messages contain PII.

## Integrations (env vars)
- MySQL: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (or `DATABASE_URL`).
- Telnyx: `TELNYX_API_KEY`, `TELNYX_FROM_NUMBER`, `TELNYX_MESSAGING_PROFILE_ID`.
  - Webhook route: `POST /webhooks/telnyx` (in `routes/api.php`, no CSRF; signature-verified).
  - Verify with `TELNYX_PUBLIC_KEY` (base64 ED25519 public key) using raw body (`$request->getContent()`) and headers `telnyx-signature-ed25519`, `telnyx-timestamp`.
  - Prefer Telnyx’s official PHP SDK (`telnyx/telnyx-php`) webhook verification (`$client->webhooks->unwrap($payload, $headers)`) over custom crypto code.
- Google Maps: `GOOGLE_MAPS_API_KEY`.
- PDFs: store locally under `storage/` (private). Use `PDF_OUTPUT_DIR` and keep files outside public web root.

## Laravel conventions
- Main files: `routes/web.php`, `routes/api.php`, `app/Http/Controllers/`, `app/Models/`, `database/migrations/`.
- Prefer conventional Laravel structure over creating a parallel `src/` tree.

## Local dev + deploy
- Local: `composer install` → `.env` from `.env.example` → `php artisan key:generate` → `php artisan migrate` → `php artisan serve --host=127.0.0.1 --port=8000`.
- Shared hosting: avoid long-lived workers; use cron + scheduler (`php artisan schedule:run`).
