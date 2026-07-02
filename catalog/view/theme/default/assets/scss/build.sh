#!/usr/bin/env sh
# ==========================================================================
# buytini — build css/main.css from SCSS partials.
#
# Партіали в scss/parts/ — це plain CSS (без $-змінних, вкладеності, міксинів),
# розбитий за секціями. "Збірка" = конкатенація партіалів у порядку @import з
# main.scss. Жодних зовнішніх залежностей (node/sass) не потрібно.
#
# SCSS — єдине джерело правди. css/main.css — згенерований артефакт:
# правки вносити ТІЛЬКИ в партіали, потім запускати цей скрипт.
#
#   sh scss/build.sh        (з каталогу .../assets)
# ==========================================================================
set -eu

cd "$(dirname "$0")/.."                       # -> .../assets
manifest="scss/main.scss"
out="css/main.css"
tmp="$out.tmp.$$"

: > "$tmp"
grep '@import' "$manifest" | sed -E 's#.*"parts/([^"]+)".*#\1#' | while IFS= read -r part; do
  file="scss/parts/_${part}.scss"
  [ -f "$file" ] || { echo "missing partial: $file" >&2; rm -f "$tmp"; exit 1; }
  cat "$file" >> "$tmp"
done
mv "$tmp" "$out"
echo "built $out"
