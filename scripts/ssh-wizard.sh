#!/usr/bin/env bash
set -euo pipefail

# Interactive helper for setting up SSH access to shared hosting and uploading the deploy zip.
#
# Run from repo root:
#   bash scripts/shared hosting-ssh-wizard.sh

DEPLOY_SSH_HOST="${DEPLOY_SSH_HOST:?Set DEPLOY_SSH_HOST env var (e.g. ssh.example.com)}"
DEPLOY_SSH_USER="${DEPLOY_SSH_USER:?Set DEPLOY_SSH_USER env var}"
DEPLOY_SSH_PORT="${DEPLOY_SSH_PORT:-18765}"
SSH_ALIAS="hosting"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ZIP_PATH="$ROOT_DIR/storage/app/deploy/webhook-proxy.zip"

echo "== shared hosting SSH wizard =="
echo

echo "This will:"
echo "  1) Help you pick your private key file"
echo "  2) Add an SSH config alias: $SSH_ALIAS"
echo "  3) Test SSH connectivity"
echo "  4) Upload: $ZIP_PATH"
echo

if [[ ! -f "$ZIP_PATH" ]]; then
  echo "Missing ZIP: $ZIP_PATH" >&2
  echo "Build it first with: bash scripts/build-webhook-proxy-zip.sh" >&2
  exit 1
fi

mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh" || true

# Find candidate private keys (files without .pub).
mapfile -t candidates < <(
  find "$HOME/.ssh" -maxdepth 1 -type f \
    ! -name "*.pub" \
    ! -name "known_hosts" \
    ! -name "known_hosts.old" \
    ! -name "authorized_keys" \
    -printf "%p\n" 2>/dev/null | sort
)

echo "Step 1/4: Choose your PRIVATE key file (not .pub)"
if [[ ${#candidates[@]} -eq 0 ]]; then
  echo "No private key files found in ~/.ssh." >&2
  echo "Put your private key file in ~/.ssh first, then rerun this." >&2
  exit 1
fi

echo
for i in "${!candidates[@]}"; do
  echo "  $((i+1))) ${candidates[$i]}"
done

echo
read -r -p "Enter a number (1-${#candidates[@]}): " choice
if ! [[ "$choice" =~ ^[0-9]+$ ]] || (( choice < 1 || choice > ${#candidates[@]} )); then
  echo "Invalid selection." >&2
  exit 1
fi

KEY_FILE="${candidates[$((choice-1))]}"
chmod 600 "$KEY_FILE" || true

echo
echo "Using key: $KEY_FILE"

echo
echo "Step 2/4: Writing SSH config alias ($SSH_ALIAS)"
CONFIG_FILE="$HOME/.ssh/config"

# Remove any existing block for this alias.
if [[ -f "$CONFIG_FILE" ]]; then
  awk -v alias="$SSH_ALIAS" '
    BEGIN {skip=0}
    $1=="Host" && $2==alias {skip=1; next}
    $1=="Host" && skip==1 {skip=0}
    skip==0 {print}
  ' "$CONFIG_FILE" > "$CONFIG_FILE.tmp" && mv "$CONFIG_FILE.tmp" "$CONFIG_FILE"
fi

cat >> "$CONFIG_FILE" <<EOF

Host $SSH_ALIAS
  HostName $DEPLOY_SSH_HOST
  User $DEPLOY_SSH_USER
  Port $DEPLOY_SSH_PORT
  IdentityFile $KEY_FILE
  IdentitiesOnly yes
EOF

chmod 600 "$CONFIG_FILE" || true

echo "Wrote: $CONFIG_FILE"

echo
echo "Step 3/4: Testing SSH (you may be prompted for your key passphrase)"
ssh -o BatchMode=no "$SSH_ALIAS" 'echo "Connected OK"; whoami'

echo
echo "Step 4/4: Uploading ZIP"
scp "$ZIP_PATH" "$SSH_ALIAS:~/webhook-proxy.zip"

echo
echo "Done. Verify on server with:"
echo "  ssh $SSH_ALIAS 'ls -lh ~/webhook-proxy.zip'"
