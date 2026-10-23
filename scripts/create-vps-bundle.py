#!/usr/bin/env python3
"""Create a source-only VPS bundle and include the legacy catalog inputs."""

from __future__ import annotations

import argparse
import os
import tarfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
EXCLUDED_DIRS = {
    ".git",
    "node_modules",
    "vendor",
    "legacy",
    "tests",
    "playwright-report",
    "test-results",
    "__pycache__",
}
EXCLUDED_FILES = {".env", "database.sqlite", ".phpunit.result.cache"}


def included(path: Path) -> bool:
    relative = path.relative_to(ROOT)
    if relative == Path("public/hot"):
        return False
    if any(part in EXCLUDED_DIRS for part in relative.parts):
        return False
    if path.suffix.lower() in {".sqlite", ".db", ".dump"}:
        return False
    if path.name in EXCLUDED_FILES or path.name.startswith(".env."):
        return path.name == ".env.example"
    if relative.parts[:2] == ("storage", "logs"):
        return False
    if relative.parts[:3] == ("storage", "framework", "testing"):
        return False
    return path.is_file()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("output", type=Path)
    args = parser.parse_args()
    output = args.output.resolve()
    output.parent.mkdir(parents=True, exist_ok=True)

    legacy_source = ROOT / "legacy" / "js" / "main.js"
    legacy_assets = ROOT / "legacy" / "aura-v2-assets" / "products"
    if not legacy_source.is_file() or not legacy_assets.is_dir():
        raise SystemExit("Legacy catalog source or product assets are missing")

    with tarfile.open(output, "w:gz") as bundle:
        for current, dirs, files in os.walk(ROOT):
            current_path = Path(current)
            dirs[:] = sorted(name for name in dirs if name not in EXCLUDED_DIRS)
            for name in sorted(files):
                path = current_path / name
                if included(path):
                    bundle.add(path, arcname=path.relative_to(ROOT).as_posix())

        bundle.add(legacy_source, arcname="legacy/js/main.js")
        for current, dirs, files in os.walk(legacy_assets):
            current_path = Path(current)
            dirs.sort()
            for name in sorted(files):
                path = current_path / name
                if path.is_file():
                    bundle.add(path, arcname=Path("legacy/aura-v2-assets/products") / path.relative_to(legacy_assets))

    print(f"Created {output.name} ({output.stat().st_size:,} bytes)")


if __name__ == "__main__":
    main()
