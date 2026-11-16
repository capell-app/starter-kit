#!/usr/bin/env python3
"""Fail when a patch or script the smoke test applies contains a line the published guides do not."""
import pathlib
import sys

patch = pathlib.Path(sys.argv[1])
guides = [pathlib.Path(path) for path in sys.argv[2:]]
published = {line.strip() for guide in guides for line in guide.read_text().splitlines() if line.strip()}
missing = []

for number, line in enumerate(patch.read_text().splitlines(), start=1):
    if patch.suffix == '.patch':
        if not line.startswith('+') or line.startswith('+++'):
            continue
        text = line[1:].strip()
    else:
        text = line.strip()
    if text and text not in published:
        missing.append((number, text))

if missing:
    print(f'{patch} has lines the published guides do not contain:')
    for number, text in missing:
        print(f'  line {number}: {text}')
    sys.exit(1)

print(f'{patch}: every line appears in the published guides')
