#!/bin/sh
# Naikkan versi server: ./bump.sh [patch|minor|major]  (file VERSION)
v=$(cat "$(dirname "$0")/VERSION"); a=${v%%.*}; r=${v#*.}; b=${r%%.*}; c=${r#*.}
case "${1:-patch}" in major) a=$((a+1)); b=0; c=0;; minor) b=$((b+1)); c=0;; *) c=$((c+1));; esac
echo "$a.$b.$c" > "$(dirname "$0")/VERSION"; echo "Versi server: $a.$b.$c"
