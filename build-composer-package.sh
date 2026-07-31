#!/usr/bin/env bash

set -euo pipefail

repository_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
conjin_dir="${repository_dir}/composer-projects/conjin"
modules_dir="${conjin_dir}/ext/modules-shared"
dhall_vendor_dir="${conjin_dir}/ext/dhall"
third_party_licenses_dir="${conjin_dir}/third-party-licenses/npm"
dist_dir="${repository_dir}/dist"
package_dir="${dist_dir}/lucques-conjin"

# Validate the tools used to select and copy the package contents.
echo "[1/5] Checking required Composer package assembly tools"
for command_name in git cp find; do
    command -v "${command_name}" >/dev/null || {
        echo "Required command is not installed: ${command_name}" >&2
        exit 1
    }
done

# Create a fresh temporary tree so a failed assembly cannot leave a partial
# package at the public staging path.
echo "[2/5] Creating clean package staging tree"
mkdir -p -- "${dist_dir}"
temporary_package_dir="$(mktemp -d "${dist_dir}/.lucques-conjin.XXXXXX")"
trap 'rm -rf -- "${temporary_package_dir}"' EXIT

# Copy the package's tracked and non-ignored source files from the working tree.
# This excludes local Composer dependencies, caches, generated assets, and other
# ignored development state while including newly created source files.
echo "[3/5] Copying package sources"
tracked_prefix="composer-projects/conjin/"
while IFS= read -r -d '' tracked_file; do
    [[ -e "${repository_dir}/${tracked_file}" ]] || continue
    relative_file="${tracked_file#"${tracked_prefix}"}"
    case "${relative_file}" in
        composer.lock|*/.gitignore|*/AGENTS.md|*/agents.md|ext/dhall/build|ext/modules-shared/_build-lib.sh|ext/modules-shared/*/build)
            continue
            ;;
    esac
    destination_file="${temporary_package_dir}/${relative_file}"
    mkdir -p -- "$(dirname -- "${destination_file}")"
    cp -a -- "${repository_dir}/${tracked_file}" "${destination_file}"
done < <(git -C "${repository_dir}" ls-files -z --cached --others --exclude-standard -- "${tracked_prefix}")

# Copy generated shared-module directories declared by their module-local
# .gitignore files. Missing or empty outputs indicate that build-assets.sh must
# be run before packaging.
echo "[4/5] Copying generated package assets"
while IFS= read -r -d '' ignore_file; do
    module_dir="$(dirname -- "${ignore_file}")"
    while IFS= read -r generated_path || [[ -n "${generated_path}" ]]; do
        [[ -z "${generated_path}" || "${generated_path}" == \#* ]] && continue
        generated_path="${generated_path#/}"
        generated_path="${generated_path%/}"
        source_path="${module_dir}/${generated_path}"
        relative_path="${source_path#"${conjin_dir}/"}"
        destination_path="${temporary_package_dir}/${relative_path}"

        if [[ ! -d "${source_path}" ]] || [[ -z "$(find "${source_path}" -mindepth 1 -print -quit)" ]]; then
            echo "Missing generated asset directory: ${source_path}" >&2
            echo "Run ./build-assets.sh before assembling the Composer package." >&2
            exit 1
        fi

        mkdir -p -- "${destination_path}"
        cp -a -- "${source_path}/." "${destination_path}/"
    done < "${ignore_file}"
done < <(find "${modules_dir}" -mindepth 2 -maxdepth 2 -type f -name .gitignore -print0 | sort -z)

copy_dhall_dependency() {
    local dependency_name="$1"
    local resolved_file="$2"
    local source_path="${dhall_vendor_dir}/${dependency_name}"
    local destination_path="${temporary_package_dir}/ext/dhall/${dependency_name}"

    for required_file in LICENSE "${resolved_file}"; do
        if [[ ! -f "${source_path}/${required_file}" ]]; then
            echo "Missing generated Dhall dependency file: ${source_path}/${required_file}" >&2
            echo "Run ./build-assets.sh before assembling the Composer package." >&2
            exit 1
        fi
    done

    mkdir -p -- "${destination_path}/$(dirname -- "${resolved_file}")"
    cp -a -- "${source_path}/LICENSE" "${destination_path}/LICENSE"
    cp -a -- "${source_path}/${resolved_file}" "${destination_path}/${resolved_file}"
}

copy_dhall_dependency "dhall-lang" "Prelude/package_resolved.dhall"
copy_dhall_dependency "dhall-docker-compose" "compose/v3/package_resolved.dhall"

echo "[5/5] Copying third-party legal files"
if [[ ! -f "${third_party_licenses_dir}/README.md" ]]; then
    echo "Missing generated npm legal files: ${third_party_licenses_dir}" >&2
    echo "Run ./build-assets.sh before assembling the Composer package." >&2
    exit 1
fi
mkdir -p -- "${temporary_package_dir}/third-party-licenses/npm"
cp -a -- "${third_party_licenses_dir}/." "${temporary_package_dir}/third-party-licenses/npm/"

rm -rf -- "${package_dir}"
mv -- "${temporary_package_dir}" "${package_dir}"
trap - EXIT
echo "Composer package staged at ${package_dir}"
