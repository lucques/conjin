#!/usr/bin/env bash

set -euo pipefail

repository_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
composer_projects_dir="${repository_dir}/composer-projects"
conjin_dir="${composer_projects_dir}/conjin"
demo_app_dir="${composer_projects_dir}/demo-app"
integration_test_app_dir="${composer_projects_dir}/integration-test-app"

# 1. Validate the tools used by the remaining setup phases. Checking everything
# up front produces a useful error before any dependencies or assets are changed.
echo "[1/6] Checking required setup tools"
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
echo "[2/6] Installing locked Conjin development dependencies"
composer install --working-dir="${conjin_dir}" --no-interaction

# 3. Install the human-facing demo app and its locked Conjin package version.
echo "[3/6] Installing locked demo-app PHP dependencies"
composer install --working-dir="${demo_app_dir}" --no-interaction

# 4. Install the deterministic integration fixture independently of the demo.
echo "[4/6] Installing locked integration-test-app PHP dependencies"
composer install --working-dir="${integration_test_app_dir}" --no-interaction

# 5. Produce the generated assets included later when assembling a
# distributable Conjin Composer package.
echo "[5/6] Building Conjin assets"
"${repository_dir}/build-assets.sh"

# 6. Write each app's machine-specific Dhall Text imports so deployment configs
# remain directly evaluable by tools and editor integrations.
echo "[6/6] Configuring Composer project deployment paths"
demo_deployments_dir="${demo_app_dir}/deployments"
demo_conjin_dir="$(readlink -f -- "${demo_app_dir}/vendor/lucques/conjin")"
printf '%s' "${demo_conjin_dir}" > "${demo_deployments_dir}/CONJIN_DIR"
printf '%s' "${demo_app_dir}" > "${demo_deployments_dir}/APP_DIR"
printf '%s' "${demo_deployments_dir}" > "${demo_deployments_dir}/DEPLOYMENTS_DIR"

integration_deployments_dir="${integration_test_app_dir}/deployments"
integration_conjin_dir="$(readlink -f -- "${integration_test_app_dir}/vendor/lucques/conjin")"
printf '%s' "${integration_conjin_dir}" > "${integration_deployments_dir}/CONJIN_DIR"
printf '%s' "${integration_test_app_dir}" > "${integration_deployments_dir}/APP_DIR"
printf '%s' "${integration_deployments_dir}" > "${integration_deployments_dir}/DEPLOYMENTS_DIR"
printf '%s' "${repository_dir}/tests/integration" > "${integration_deployments_dir}/test/PLAYWRIGHT_TESTS_DIR"
