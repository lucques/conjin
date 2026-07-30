#!/usr/bin/env bash

set -euo pipefail

repository_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
composer_projects_dir="${repository_dir}/composer-projects"
conjin_dir="${composer_projects_dir}/conjin"
demo_app_dir="${composer_projects_dir}/demo-app"
integration_test_app_dir="${composer_projects_dir}/integration-test-app"
modules_dir="${conjin_dir}/ext/modules-shared"

# 1. Validate the tools used by the remaining setup phases. Checking everything
# up front produces a useful error before any dependencies or assets are changed.
echo "[1/8] Checking required setup tools"
for command_name in php composer npm curl sha256sum tar unzip dhall dhall-to-json docker; do
    command -v "${command_name}" >/dev/null || {
        echo "Required command is not installed: ${command_name}" >&2
        exit 1
    }
done

minimum_docker_major=26
docker_version="$(docker version --format '{{.Client.Version}}' 2>/dev/null || true)"
if [[ -z "${docker_version}" ]]; then
    echo "Could not determine the installed Docker client version." >&2
    exit 1
fi
docker_major="${docker_version%%.*}"

if [[ ! "${docker_major}" =~ ^[0-9]+$ ]] || ((docker_major < minimum_docker_major)); then
    echo "Docker ${minimum_docker_major} or newer is required; found ${docker_version}." >&2
    exit 1
fi

docker compose version --short >/dev/null 2>&1 || {
    echo "The Docker Compose plugin is required." >&2
    exit 1
}

if ! docker info >/dev/null 2>&1; then
    echo "Warning: Docker is installed, but its daemon is not currently reachable." >&2
fi

# 2. Install the exact PHP dependency versions recorded in composer.lock.
echo "[2/8] Installing locked Conjin development dependencies"
composer install --working-dir="${conjin_dir}" --no-interaction

# 3. Install the human-facing demo app and its locked Conjin package version.
echo "[3/8] Installing locked demo-app PHP dependencies"
composer install --working-dir="${demo_app_dir}" --no-interaction

# 4. Install the deterministic integration fixture independently of the demo.
echo "[4/8] Installing locked integration-test-app PHP dependencies"
composer install --working-dir="${integration_test_app_dir}" --no-interaction

# 5. Recreate node_modules from package-lock.json. Lifecycle scripts and optional
# Node-only tooling are omitted because Conjin consumes prebuilt browser assets.
echo "[5/8] Installing locked browser dependencies"
npm ci --prefix "${repository_dir}" --ignore-scripts --omit=optional

# 6. Download checksum-pinned Dhall sources and resolve their remote imports so
# deployment configuration does not depend on mutable network resources.
echo "[6/8] Building pinned Dhall dependencies"
(cd -- "${conjin_dir}/deployment/dhall/vendor" && ./build)

# 7. Stage each shared module's allowlisted runtime files from node_modules,
# Composer, or a checksum-pinned archive into the paths expected by the app.
echo "[7/8] Building shared-module assets"
while IFS= read -r -d '' build_script; do
    module_dir="$(dirname -- "${build_script}")"
    echo "  Building ${build_script#"${repository_dir}/"}"
    (cd -- "${module_dir}" && ./build)
done < <(find "${modules_dir}" -mindepth 2 -maxdepth 2 -type f -name build -print0 | sort -z)

# 8. Write each app's ignored machine-specific Dhall Text imports so deployment
# configs remain directly evaluable by tools and editor integrations.
echo "[8/8] Configuring Composer project deployment paths"
rclone_config_path="${XDG_CONFIG_HOME:-${HOME}/.config}/rclone/rclone.conf"

demo_deployments_dir="${demo_app_dir}/deployments"
demo_conjin_dir="$(readlink -f -- "${demo_app_dir}/vendor/lucques/conjin")"
printf '%s' "${demo_conjin_dir}" > "${demo_deployments_dir}/CONJIN_DIR"
printf '%s' "${demo_app_dir}" > "${demo_deployments_dir}/APP_DIR"
printf '%s' "${demo_deployments_dir}" > "${demo_deployments_dir}/DEPLOYMENTS_DIR"
printf '%s' "${rclone_config_path}" > "${demo_deployments_dir}/dcd/src/RCLONE_CONFIG_PATH"

integration_deployments_dir="${integration_test_app_dir}/deployments"
integration_conjin_dir="$(readlink -f -- "${integration_test_app_dir}/vendor/lucques/conjin")"
printf '%s' "${integration_conjin_dir}" > "${integration_deployments_dir}/CONJIN_DIR"
printf '%s' "${integration_test_app_dir}" > "${integration_deployments_dir}/APP_DIR"
printf '%s' "${integration_deployments_dir}" > "${integration_deployments_dir}/DEPLOYMENTS_DIR"
printf '%s' "${repository_dir}/tests/integration" > "${integration_deployments_dir}/test/PLAYWRIGHT_TESTS_DIR"
