#!/usr/bin/env bash
set -euo pipefail

# One-stop interactive wizard for shared hosting SSH + uploading the deployment ZIP.
#
# What it does:
# - Ensures deploy ZIP exists (builds it if missing)
# - Saves your hosting private key into ~/.ssh (paste into terminal)
# - (Optional) compares derived public key with the hosting public key you paste
# - Creates an SSH config alias
# - Tests SSH connectivity
# - Uploads the ZIP via scp

DEPLOY_SSH_HOST_DEFAULT="${DEPLOY_SSH_HOST:-}"
DEPLOY_SSH_USER_DEFAULT="${DEPLOY_SSH_USER:-}"
DEPLOY_SSH_PORT_DEFAULT="${DEPLOY_SSH_PORT:-18765}"
SSH_ALIAS_DEFAULT="hosting"
KEY_PATH_DEFAULT="$HOME/.ssh/hosting_deploy"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ZIP_PATH="$ROOT_DIR/storage/app/deploy/webhook-proxy.zip"

print() { printf '%s\n' "$*"; }

prompt() {
  local var_name="$1"
  local label="$2"
  local default_value="$3"

  local value
  read -r -p "$label [$default_value]: " value || true
  if [[ -z "${value:-}" ]]; then
    value="$default_value"
  fi
  printf -v "$var_name" '%s' "$value"
}

ensure_zip() {
  if [[ -f "$ZIP_PATH" ]]; then
    return 0
  fi

  print "Deploy ZIP not found at: $ZIP_PATH"
  print "Building it now..."
  bash "$ROOT_DIR/scripts/build-webhook-proxy-zip.sh"

  if [[ ! -f "$ZIP_PATH" ]]; then
    print "Failed to build ZIP." >&2
    exit 1
  fi
}

write_key_file() {
  local key_path="$1"
  mkdir -p "$(dirname "$key_path")"
  chmod 700 "$HOME/.ssh" || true

  print
  print "Paste your hosting PRIVATE KEY now."
  print "- It must include the BEGIN/END lines."
  print "- When finished, type a single line containing: END"
  print

  : > "$key_path"

  # Read until a line that is exactly END
  while IFS= read -r line; do
    if [[ "$line" == "END" ]]; then
      break
    fi
    printf '%s\n' "$line" >> "$key_path"
  done

  # Normalize CRLF if pasted from Windows clipboard
  sed -i 's/\r$//' "$key_path" || true
  chmod 600 "$key_path" || true

  if ! grep -q "BEGIN OPENSSH PRIVATE KEY" "$key_path" 2>/dev/null; then
    print "Key file does not look like an OpenSSH private key (missing BEGIN line)." >&2
    print "Re-run and paste the full block from your hosting panel." >&2
    exit 1
  fi
}

derive_public_key() {
  local key_path="$1"
  local out_path="$2"

  # ssh-keygen -y will prompt for passphrase if the key is encrypted
  if ! ssh-keygen -y -f "$key_path" > "$out_path"; then
    print "Failed to derive public key from private key." >&2
    print "Possible causes: wrong key content, missing lines, or incorrect passphrase." >&2
    exit 1
  fi
}

optional_compare_pubkeys() {
  local derived_pub_path="$1"

  print
  print "Optional: paste the hosting PUBLIC KEY to confirm it matches."
  print "- If you want to skip this, just press Enter at the next prompt."
  print

  local first_line
  read -r -p "Paste hosting public key now (or press Enter to skip): " first_line || true

  if [[ -z "${first_line:-}" ]]; then
    print "Skipping public key comparison."
    return 0
  fi

  local shared hosting_pub="/tmp/hosting.pub"
  printf '%s\n' "$first_line" > "$shared hosting_pub"

  if diff -u "$derived_pub_path" "$shared hosting_pub" >/dev/null 2>&1; then
    print "Public key match: OK"
  else
    print "Public key match: NO" >&2
    print "Your WSL private key does NOT match the hosting public key you pasted." >&2
    print "Fix: use the matching private key for that shared hosting key (re-paste it)." >&2
    exit 1
  fi
}

write_ssh_config_alias() {
  local alias_name="$1"
  local host="$2"
  local user="$3"
  local port="$4"
  local key_path="$5"

  local config_file="$HOME/.ssh/config"

  # Remove any existing block for this alias.
  if [[ -f "$config_file" ]]; then
    awk -v alias="$alias_name" '
      BEGIN {skip=0}
      $1=="Host" && $2==alias {skip=1; next}
      $1=="Host" && skip==1 {skip=0}
      skip==0 {print}
    ' "$config_file" > "$config_file.tmp" && mv "$config_file.tmp" "$config_file"
  fi

  cat >> "$config_file" <<EOF

Host $alias_name
  HostName $host
  User $user
  Port $port
  IdentityFile $key_path
  IdentitiesOnly yes
EOF

  chmod 600 "$config_file" || true
}

test_ssh() {
  local alias_name="$1"
  print
  print "Testing SSH (you may be prompted for a passphrase)..."
  ssh -o BatchMode=no "$alias_name" 'echo Connected_OK; whoami'
}

upload_zip() {
  local alias_name="$1"
  print
  print "Uploading ZIP: $ZIP_PATH"
  scp "$ZIP_PATH" "$alias_name:~/webhook-proxy.zip"
  print "Upload complete."
  print "Verify on server: ssh $alias_name 'ls -lh ~/webhook-proxy.zip'"
}

main() {
  print "== Webhook proxy deploy wizard =="

  ensure_zip

  local site_host site_user site_port ssh_alias key_path
  prompt site_host "SSH hostname" "$DEPLOY_SSH_HOST_DEFAULT"
  prompt site_user "SSH username" "$DEPLOY_SSH_USER_DEFAULT"
  prompt site_port "SSH port" "$DEPLOY_SSH_PORT_DEFAULT"
  prompt ssh_alias "SSH alias to create in ~/.ssh/config" "$SSH_ALIAS_DEFAULT"
  prompt key_path "Where to save the private key" "$KEY_PATH_DEFAULT"

  write_key_file "$key_path"

  local derived_pub="/tmp/derived.pub"
  derive_public_key "$key_path" "$derived_pub"

  optional_compare_pubkeys "$derived_pub"

  write_ssh_config_alias "$ssh_alias" "$site_host" "$site_user" "$site_port" "$key_path"

  test_ssh "$ssh_alias"

  upload_zip "$ssh_alias"

  print
  print "Next step (on server): unzip + composer install + symlink public/" 
  print "(see README Deploying to shared hosting section)."
}

main "$@"
