#!/usr/bin/env bash

set -euo pipefail

# Initialize paths shared by a module build script.
dependency_build_init() {
    module_dir="$1"
    conjin_dir="$(cd -- "${module_dir}/../../.." && pwd)"
    repository_dir="$(cd -- "${conjin_dir}/../.." && pwd)"
}

# Empty generated output directories without replacing them. Docker bind mounts
# keep referring to the original directory inode while containers exist.
clear_outputs() {
    local output

    for output in "$@"; do
        mkdir -p -- "${module_dir:?}/${output}"
        find "${module_dir}/${output}" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
    done
}

# Copy one required dependency file into the module directory.
copy_file() {
    local source_file="$1"
    local destination="$2"

    test -f "${source_file}" || {
        echo "Missing dependency file: ${source_file}" >&2
        exit 1
    }
    mkdir -p -- "${module_dir}/$(dirname -- "${destination}")"
    install -m 0644 -- "${source_file}" "${module_dir}/${destination}"
}

# Copy one required dependency directory into the module directory.
copy_tree() {
    local source_dir="$1"
    local destination="$2"

    test -d "${source_dir}" || {
        echo "Missing dependency directory: ${source_dir}" >&2
        exit 1
    }
    mkdir -p -- "${module_dir}/${destination}"
    cp -a -- "${source_dir}/." "${module_dir}/${destination}/"
}
