# Setup

Set up a Conjin checkout by installing its system requirements and downloading its project dependencies.

## System requirements

The following software must be installed:

- Docker 26 or newer
- `secret-tool` is recommended for password handling

## Project dependencies

Run `./setup.sh` from the repository root. It performs the dependency setup in this order:

1. **Check required setup tools:** The script verifies all tools needed by the complete checkout, including Composer, npm, download and archive tools, Dhall, Docker 26 or newer, and the Docker Compose plugin before changing dependencies or generated assets. An unreachable Docker daemon produces a warning but does not stop dependency setup.
2. **Install locked Conjin development dependencies:** Composer installs the versions in `composer-projects/conjin/composer.lock` into `composer-projects/conjin/vendor`.
3. **Install the demo app:** Composer installs `composer-projects/demo-app/composer.lock`; its path repository exposes the sibling Conjin package through `vendor/lucques/conjin`.
4. **Install the integration-test app:** Composer independently installs `composer-projects/integration-test-app/composer.lock`, keeping the automated fixture separate from the demo.
5. **Build Conjin assets:** `build-assets.sh` installs the locked browser dependencies, collects their legal metadata and notice files, downloads and resolves the pinned Dhall dependencies, and stages the shared-module assets in the development tree.
6. **Configure application deployment paths:** The script writes ignored, machine-specific path files for both applications, the integration-test source path for the fixture, and the standard rclone configuration path for the demo (`$XDG_CONFIG_HOME/rclone/rclone.conf`, or `$HOME/.config/rclone/rclone.conf`). Dhall reads these files directly, so the configs also work in editor integrations without environment variables.

Run `./build-assets.sh` directly after changing a browser dependency, a pinned Dhall dependency, or an external-module asset build. It verifies its npm, Python, download, archive, checksum, and Dhall tools; recreates the repository-level `node_modules` from `package-lock.json`; collects npm package metadata and legal files under `composer-projects/conjin/third-party-licenses/npm/`; and writes the generated external-module assets and pinned Dhall sources into the development package directory. Ordinary PHP source changes do not require an asset rebuild.

Run `./build-composer-package.sh` only when preparing a package for distribution. It copies the tracked and non-ignored Conjin package sources from the working tree together with the existing generated assets and collected npm legal files into a fresh ignored staging tree at `dist/lucques-conjin`. It omits the development-only `composer.lock`, build scripts, `.gitignore` files, and agent instructions, does not rebuild dependencies, include local Composer `vendor` files, check Docker, or modify the development package tree. The command fails with an instruction to run `build-assets.sh` when generated assets or legal files are missing.

The `Build Composer package` GitHub Actions workflow performs the complete setup, test, assembly, validation, and ZIP creation process for manual distribution. See [Composer package distribution](./composer_package_distribution.md) for downloading and installing the resulting artifact without Packagist.

The module asset folders are generated outputs. Do not edit them directly. Public browser dependencies used to build those assets are declared in the root `package.json`. Framework PHP dependencies are declared in `composer-projects/conjin/composer.json`, while applications such as the demo app declare their dependency on Conjin in their own `composer.json`.

The root `.npmrc` also enforces disabled lifecycle scripts and omission of optional Node-only tooling for direct npm commands.

Dhall is the exception because no suitable package contains the required distribution. Its build script uses immutable URLs and SHA-256 checksums.

Run setup or `build-assets.sh` before `build-composer-package.sh` so the staged package contains the generated external-module assets and pinned Dhall sources. Consumers of a prepared distribution do not run these repository build steps after `composer install`.

Regenerate the repository's dependency-license inventory with `./licenses/generate-report` after changing a Composer or npm lockfile. CI runs `./licenses/generate-report --check` to reject stale reports. Before public distribution, review `composer-projects/conjin/THIRD_PARTY_NOTICES.md` for the current inventory-based release status.
