#!/bin/sh
# Runs on the PDF generator server (not on the api server), from cron. Posts the disk usage of the file system
# that holds the generated files to the api; the api stores it, shows it (Admin -> Overzichten) and mails when it is critical.
#
#   crontab -e (as the user that owns the files):
#     0 * * * * /home/examenfit/report-disk-usage.sh >> /home/examenfit/report-disk-usage.log 2>&1
#
# Config in ~/.disk-usage.env (chmod 600):
#     API_URL=https://api.examenfit.nl
#     TOKEN=<the same value as PDF_DISK_USAGE_TOKEN in the api .env>
#     DISK_PATH=/        # any path on the file system that fills up (default /)
set -eu

. "${CONFIG:-$HOME/.disk-usage.env}"
DISK_PATH="${DISK_PATH:-/}"

# df -P: one line per file system, in the POSIX layout; -m: megabytes. Columns: used (3), available (4), use% (5)
usage="$(df -Pm "$DISK_PATH" | awk 'NR == 2 { gsub("%", "", $5); print $3, $4, $5 }')"
read -r used available percentage <<EOT
$usage
EOT

curl --fail --silent --show-error --max-time 20 \
  --request POST "$API_URL/api/pdf-generator/disk-usage" \
  --header "X-Disk-Usage-Token: $TOKEN" \
  --header 'Content-Type: application/json' \
  --header 'Accept: application/json' \
  --data "{\"percentage\": $percentage, \"used_mb\": $used, \"available_mb\": $available}"
echo " $(date '+%Y-%m-%d %H:%M') $percentage%"
