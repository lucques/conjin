#!/usr/bin/env bash

set -euo pipefail

repository_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
conjin_dir="${repository_dir}/composer-projects/conjin"
modules_dir="${conjin_dir}/ext/modules-shared"

# 1. Validate the tools used to produce the generated package assets.
echo "[1/5] Checking required asset build tools"
for command_name in npm python3 curl sha256sum tar unzip dhall; do
    command -v "${command_name}" >/dev/null || {
        echo "Required command is not installed: ${command_name}" >&2
        exit 1
    }
done

# 2. Recreate node_modules from package-lock.json. Lifecycle scripts and optional
# Node-only tooling are omitted because Conjin consumes prebuilt browser assets.
echo "[2/5] Installing locked browser dependencies"
npm ci --prefix "${repository_dir}" --ignore-scripts --omit=optional

# 3. Preserve the legal metadata and notice files supplied with the locked npm
# dependencies. The assembled Composer package includes this generated tree.
echo "[3/5] Collecting npm legal files"
"${repository_dir}/licenses/collect-npm-legal-files" \
    "${repository_dir}" \
    "${conjin_dir}/third-party-licenses/npm"

# 4. Download checksum-pinned Dhall sources and resolve their remote imports so
# deployment configuration does not depend on mutable network resources.
echo "[4/5] Building pinned Dhall dependencies"
(cd -- "${conjin_dir}/ext/dhall" && ./build)

# 5. Stage each shared module's allowlisted runtime files from node_modules or a
# checksum-pinned archive into the paths expected by the development checkout.
echo "[5/5] Building shared-module assets"
while IFS= read -r -d '' build_script; do
    module_dir="$(dirname -- "${build_script}")"
    echo "  Building ${build_script#"${repository_dir}/"}"
    (cd -- "${module_dir}" && ./build)
done < <(find "${modules_dir}" -mindepth 2 -maxdepth 2 -type f -name build -print0 | sort -z)
