#!/usr/bin/env bash
# Check Markdown relative links in docs/ and top-level docs for broken targets.
# External links (http/https/mailto) and anchor-only links (#...) are skipped.
# Usage: tools/check-links.sh [root_dir]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

python3 - "$ROOT" <<'PY'
import os
import re
import sys

root = sys.argv[1]
errors = []

scan_roots = [
    os.path.join(root, 'docs'),
    os.path.join(root, 'README.md'),
    os.path.join(root, 'AGENTS.md'),
    os.path.join(root, 'DISCLAIMER.md'),
]

md_files: list[str] = []
for sr in scan_roots:
    if os.path.isfile(sr):
        md_files.append(sr)
    elif os.path.isdir(sr):
        for dirpath, _, filenames in os.walk(sr):
            for fn in sorted(filenames):
                if fn.endswith('.md'):
                    md_files.append(os.path.join(dirpath, fn))

INLINE_LINK = re.compile(r'\[(?:[^\]]*)\]\(([^)]+)\)')
REF_LINK = re.compile(r'^\[(?:[^\]]+)\]:\s*(\S+)', re.MULTILINE)

def is_external(href: str) -> bool:
    return href.startswith(('http://', 'https://', 'mailto:'))

for md_file in sorted(md_files):
    with open(md_file, encoding='utf-8') as f:
        content = f.read()

    dir_ = os.path.dirname(md_file)
    hrefs = [m.group(1).strip() for m in INLINE_LINK.finditer(content)]
    hrefs += [m.group(1).strip() for m in REF_LINK.finditer(content)]

    for href in hrefs:
        if not href or is_external(href) or href.startswith('#'):
            continue

        # strip anchor fragment
        path = href.split('#')[0]
        if not path:
            continue

        resolved = os.path.normpath(os.path.join(dir_, path))

        if not os.path.exists(resolved):
            rel_md = os.path.relpath(md_file, root)
            errors.append(f'{rel_md}: broken link → {href}')

if errors:
    for e in sorted(errors):
        print(f'FAIL: {e}', file=sys.stderr)
    print(f'\nLink check failed: {len(errors)} broken link(s).', file=sys.stderr)
    sys.exit(1)

print(f'Link check passed: {len(md_files)} file(s) scanned, no broken relative links.')
PY
