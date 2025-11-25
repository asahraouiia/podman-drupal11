#!/usr/bin/env bash
# Heuristic check: ensure files changed in the last commit are referenced in docs or CHANGELOG.md
# Usage: ./scripts/check-docs-updated.sh

set -euo pipefail

# Ensure we're in a git repo
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "ERROR: Not a git repository. This check should run from repository root." >&2
  exit 1
fi

# Ensure there is at least one commit
if ! git rev-parse --verify HEAD >/dev/null 2>&1; then
  echo "No commits found — skipping docs check." >&2
  exit 0
fi

# Get files changed in the last commit
changed_files=$(git show --name-only --pretty="" HEAD | sed '/^$/d')
if [ -z "$changed_files" ]; then
  echo "No files changed in last commit — nothing to check.";
  exit 0
fi

# Read docs/ and CHANGELOG.md content to search for references
docs_content=""
if [ -d docs ]; then
  docs_content=$(grep -R --line-number --binary-files=without-match -I "" docs || true)
fi
changelog_content=""
if [ -f CHANGELOG.md ]; then
  changelog_content=$(cat CHANGELOG.md || true)
fi

missing=0
for f in $changed_files; do
  # skip docs and changelog changes themselves
  case "$f" in
    docs/*|CHANGELOG.md) continue ;;
  esac

  # check if file path appears in docs or CHANGELOG.md
  if echo "$docs_content" | grep -Fq "$f" || echo "$changelog_content" | grep -Fq "$f"; then
    echo "OK: $f referenced in docs/ or CHANGELOG.md"
  else
    echo "MISSING DOC: $f is not referenced in docs/ or CHANGELOG.md" >&2
    missing=$((missing+1))
  fi
done

if [ $missing -gt 0 ]; then
  echo "\nDocs check failed: $missing modified file(s) are not referenced in docs/ or CHANGELOG.md." >&2
  echo "Please update the documentation per the repo policy (add a short note to CHANGELOG.md or the relevant docs)." >&2
  exit 2
fi

echo "Docs check passed — all modified files are referenced." 
exit 0
