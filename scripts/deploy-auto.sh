#!/usr/bin/env bash
set -euo pipefail

# shared hosting deploy helper that avoids pasting PRIVATE KEYS.
#
# Flow:
#  1) Build deploy ZIP (if missing)
# It supports TWO modes:
#  A) Import mode: generate an RSA key locally and ask you to import the ONE-LINE public key in your hosting panel.
#  B) hosting-generated mode: generate a key in your hosting panel and copy its PRIVATE KEY to your clipboard; this script
#     reads it from Windows clipboard into WSL and uses it to SSH.
#
# Why RSA for import: many hosting control panels reject ed25519 imports.

DEPLOY_SSH_HOST_DEFAULT="${DEPLOY_SSH_HOST:-}"
DEPLOY_SSH_USER_DEFAULT="${DEPLOY_SSH_USER:-}"
DEPLOY_SSH_PORT_DEFAULT="${DEPLOY_SSH_PORT:-18765}"
SSH_ALIAS_DEFAULT="hosting"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ZIP_PATH="$ROOT_DIR/storage/app/deploy/webhook-proxy.zip"

KEY_DIR="$HOME/.ssh"
KEY_PATH="$KEY_DIR/hosting_deploy_rsa"
KEY_PUB_PATH="$KEY_PATH.pub"

HOSTING_KEY_PATH="$KEY_DIR/hosting_deploy"

prompt() {
  local var_name="$1" label="$2" default_value="$3"
  local value
  read -r -p "$label [$default_value]: " value || true
  if [[ -z "${value:-}" ]]; then value="$default_value"; fi
  printf -v "$var_name" '%s' "$value"
}

ensure_zip() {
  if [[ -f "$ZIP_PATH" ]]; then return 0; fi
  echo "Deploy ZIP not found. Building..."
  bash "$ROOT_DIR/scripts/build-webhook-proxy-zip.sh"
  [[ -f "$ZIP_PATH" ]] || { echo "ZIP build failed." >&2; exit 1; }
}

generate_key_if_missing() {
  mkdir -p "$KEY_DIR"
  chmod 700 "$KEY_DIR" || true

  if [[ -f "$KEY_PATH" && -f "$KEY_PUB_PATH" ]]; then
    chmod 600 "$KEY_PATH" || true
    return 0
  fi

  echo "Generating RSA keypair at: $KEY_PATH"
  # No passphrase to reduce friction; you can add one later if desired.
  ssh-keygen -t rsa -b 4096 -C "hosting-deploy" -f "$KEY_PATH" -N ""
  chmod 600 "$KEY_PATH" || true
}

read_windows_clipboard_to_file() {
  local out_path="$1"

  if ! command -v powershell.exe >/dev/null 2>&1; then
    echo "powershell.exe not found in WSL; cannot read Windows clipboard." >&2
    echo "Fallback: paste the key manually into the file: $out_path" >&2
    return 1
  fi

  mkdir -p "$(dirname "$out_path")"
  chmod 700 "$KEY_DIR" || true

  # Read clipboard as raw text and normalize CRLF.
  powershell.exe -NoProfile -Command Get-Clipboard -Raw | tr -d '\r' > "$out_path"
  chmod 600 "$out_path" || true

  if ! grep -q "BEGIN OPENSSH PRIVATE KEY" "$out_path" 2>/dev/null; then
    echo "Clipboard does not look like an OpenSSH private key (missing BEGIN line)." >&2
    echo "Make sure you copied the PRIVATE KEY from your hosting panel (not the public key)." >&2
    return 1
  fi

  return 0
}

validate_private_key_parses() {
  local key_path="$1"
  local derived_pub_path="$2"

  # This will prompt for passphrase if the key is encrypted.
  ssh-keygen -y -f "$key_path" > "$derived_pub_path"
}

write_ssh_config_alias() {
  local alias_name="$1" host="$2" user="$3" port="$4" identity_file="${5:-$KEY_PATH}"
  local config_file="$KEY_DIR/config"

  # Remove existing block for alias if present.
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
  IdentityFile $identity_file
  IdentitiesOnly yes
EOF

  chmod 600 "$config_file" || true
}

main() {
  echo "== Webhook proxy deploy (no private-key paste) =="

  ensure_zip

  local host user port alias_name
  prompt host "SSH hostname" "$DEPLOY_SSH_HOST_DEFAULT"
  prompt user "SSH username" "$DEPLOY_SSH_USER_DEFAULT"
  prompt port "SSH port" "$DEPLOY_SSH_PORT_DEFAULT"
  prompt alias_name "SSH alias to create" "$SSH_ALIAS_DEFAULT"

  generate_key_if_missing
  write_ssh_config_alias "$alias_name" "$host" "$user" "$port"

  echo
  echo "Choose a mode:"
  echo "  1) Import mode (recommended if your host lets you import public keys)"
  echo "  2) hosting-generated key mode (if your host does NOT support import)"
  echo
  read -r -p "Enter 1 or 2 [2]: " mode
  mode="${mode:-2}"

  if [[ "$mode" == "1" ]]; then
    echo
    echo "STEP 1: Add this PUBLIC KEY in your hosting panel → Dev Tools → SSH Keys Manager (Import)"
    echo "Key name can be anything (e.g. claude3-wsl)."
    echo
    cat "$KEY_PUB_PATH"
    echo
    read -r -p "Press Enter AFTER you have imported this public key in your hosting panel..." _
  else
    echo
    echo "STEP 1: In your hosting panel, click Generate New SSH Key."
    echo "Then open that key's Private Key and click Copy to Clipboard."
    echo "(You are copying to Windows clipboard.)"
    echo
    read -r -p "Press Enter AFTER you've copied the PRIVATE KEY to clipboard..." _

    echo "Reading private key from Windows clipboard into: $HOSTING_KEY_PATH"
    read_windows_clipboard_to_file "$HOSTING_KEY_PATH"

    echo "Validating key parses (you may be prompted for a passphrase)..."
    validate_private_key_parses "$HOSTING_KEY_PATH" /tmp/hosting-derived.pub

    # Update SSH config alias to use the hosting-generated key.
    write_ssh_config_alias "$alias_name" "$host" "$user" "$port" "$HOSTING_KEY_PATH"
  fi

  echo
  echo "STEP 2: Testing SSH (if this fails, key isn't accepted yet)"
  ssh -o BatchMode=no "$alias_name" 'echo Connected_OK; whoami'

  echo
  echo "STEP 3: Uploading deploy ZIP"
  scp "$ZIP_PATH" "$alias_name:~/webhook-proxy.zip"

  echo
  echo "Uploaded: ~/webhook-proxy.zip"
  echo "Verify: ssh $alias_name 'ls -lh ~/webhook-proxy.zip'"
}

main "$@"
