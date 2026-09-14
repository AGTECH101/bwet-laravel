#!/usr/bin/env python3
"""
compile_code.py

Walks a project directory, finds every .php, .blade.php, and composer.json
file, and writes them all into one labeled text file (proj_code.txt) so you
can upload a single file to an AI to give it context on your project.

USAGE:
    python compile_code.py                # runs on current folder
    python compile_code.py /path/to/project
    python compile_code.py /path/to/project my_output.txt
"""

import os
import sys

# Folders to skip entirely (vendor code, dependencies, build artifacts, etc.)
EXCLUDE_DIRS = {
    "vendor", "node_modules", ".git", "bootstrap/cache",
    "storage", ".well-known", "public/build", ".idea", ".vscode"
}

# File extensions/names to include
INCLUDE_EXTS = (".php", ".blade.php")
INCLUDE_NAMES = {"composer.json"}


def should_skip_dir(dirpath, root):
    """Check if a directory path (relative to root) matches an excluded dir."""
    rel = os.path.relpath(dirpath, root).replace("\\", "/")
    parts = rel.split("/")
    for excl in EXCLUDE_DIRS:
        excl_parts = excl.split("/")
        if parts[:len(excl_parts)] == excl_parts:
            return True
        if excl in parts:
            return True
    return False


def is_target_file(filename):
    if filename in INCLUDE_NAMES:
        return True
    for ext in INCLUDE_EXTS:
        if filename.endswith(ext):
            return True
    return False


def main():
    root = sys.argv[1] if len(sys.argv) > 1 else "."
    output_file = sys.argv[2] if len(sys.argv) > 2 else "proj_code.txt"
    root = os.path.abspath(root)

    matched_files = []

    for dirpath, dirnames, filenames in os.walk(root):
        # prune excluded directories in-place so os.walk doesn't descend into them
        dirnames[:] = [
            d for d in dirnames
            if not should_skip_dir(os.path.join(dirpath, d), root)
        ]

        for filename in sorted(filenames):
            if is_target_file(filename):
                full_path = os.path.join(dirpath, filename)
                rel_path = os.path.relpath(full_path, root).replace("\\", "/")
                matched_files.append((rel_path, full_path))

    matched_files.sort(key=lambda x: x[0])

    with open(output_file, "w", encoding="utf-8") as out:
        for rel_path, full_path in matched_files:
            out.write(f"{rel_path}:\n")
            try:
                with open(full_path, "r", encoding="utf-8", errors="replace") as f:
                    out.write(f.read())
            except Exception as e:
                out.write(f"[Could not read file: {e}]")
            out.write("\n\n")

    print(f"Done. {len(matched_files)} files compiled into '{output_file}'.")
    print(f"Scanned root: {root}")


if __name__ == "__main__":
    main()