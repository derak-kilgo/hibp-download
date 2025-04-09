#!/bin/bash
# This script downloads the latest Pwned Passwords data files from the API.
# It's a bash alternative to the pure php version.

set -euo pipefail

# Configuration
OUT_DIR="../storage/data"
CONCURRENCY=100        # Max concurrent downloads (tune this)
RETRY=10               # Curl retry attempts
BASE_URL="https://api.pwnedpasswords.com/range"

mkdir -p "$OUT_DIR"

echo "Starting download to: $OUT_DIR"
echo "Max concurrency: $CONCURRENCY"
echo "Resumable: Yes (will skip valid existing files)"
echo "-----------------------------"

download_range() {
    range="$1"
    outfile="${OUT_DIR}/${range}"

    if [[ -f "$outfile" ]]; then
        filesize=$(stat --printf="%s" "$outfile")
        if [[ "$filesize" -ge 1024 ]]; then
            echo "✓ $range already exists and is valid, skipping."
            return
        else
            echo "⚠️  $range exists but is too small (${filesize} bytes), re-downloading."
        fi
    fi

    echo "⬇️  Downloading $range"
    curl -s --retry "$RETRY" --retry-all-errors \
        -A 'HibpShellDownloader/0.1' \
        "$BASE_URL/$range" -o "$outfile"
    
    if [[ $? -eq 0 ]]; then
        echo "✅ Finished $range"
    else
        echo "❌ Failed $range"
    fi
}

export -f download_range
export OUT_DIR BASE_URL RETRY

# Generate all 16^5 = 1,048,576 5-char hex ranges
seq 0 $((16#FFFFF)) | xargs -P "$CONCURRENCY" -n 1 -I {} bash -c '
    range=$(printf "%05X" {})
    download_range "$range"
'
