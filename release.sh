#!/usr/bin/env bash
set -euo pipefail

# Cách dùng: ./release.sh 1.1.2 "Tóm tắt ngắn gọn thay đổi của bản 1.1.2"
if [ $# -lt 1 ]; then
  echo "Cách dùng: ./release.sh <phien_ban> [\"Tom tat cap nhat\"]"
  echo "Ví dụ:    ./release.sh 1.1.2 \"Thêm Push Notification FCM cho Mobile App\""
  exit 1
fi

VER="${1#v}"
SUMMARY="${2:-Cập nhật phiên bản v$VER}"
TODAY="$(date +%Y-%m-%d)"
REL_FILE="releases/v${VER}.md"

mkdir -p releases
echo "$VER" > VERSION

if [ ! -f "$REL_FILE" ]; then
  cat > "$REL_FILE" <<EOF
# Ghi chú phát hành — Phiên bản v${VER}

- **Ngày phát hành:** ${TODAY}
- **Tag GitHub:** \`v${VER}\`
- **Nhánh:** \`main\`

---

## Những gì đã cập nhật trong bản v${VER}

- ${SUMMARY}
EOF
  echo "Đã tạo file mô tả: $REL_FILE (hãy bổ sung chi tiết nếu cần)."
fi

if ! grep -q "releases/v${VER}.md" CHANGELOG.md; then
  awk -v ver="$VER" -v dt="$TODAY" -v sum="$SUMMARY" '
    /^\|---\|/ {
      print
      print "| **v" ver "** | " dt " | [releases/v" ver ".md](./releases/v" ver ".md) | " sum " |"
      next
    }
    { print }
  ' CHANGELOG.md > CHANGELOG.tmp && mv CHANGELOG.tmp CHANGELOG.md
fi

git add -A
git commit -m "Release v${VER}: ${SUMMARY}"
git tag -a "v${VER}" -m "Release v${VER}: ${SUMMARY}"
git push origin main --tags

echo "Đã phát hành thành công v${VER} lên GitHub kèm tag và file $REL_FILE!"
