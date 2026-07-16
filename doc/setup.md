# Setup

Set up a Conjin checkout by installing its system requirements and downloading its project dependencies.

## System requirements

The following software must be installed:

- Docker 26 or newer
- `secret-tool` is recommended for password handling

## Project dependencies

Run `./setup.sh` from the repository root. It performs the dependency setup in this order:

1. **Check required setup tools:** The script verifies that Composer, npm, curl, SHA-256 tooling, tar, unzip, Dhall, Docker 26 or newer, and the Docker Compose plugin are available before changing dependencies or generated assets. An unreachable Docker daemon produces a warning but does not stop dependency setup.
2. **Install locked Conjin development dependencies:** Composer installs the versions in `composer-projects/conjin/composer.lock` into `composer-projects/conjin/vendor`.
3. **Install the demo app:** Composer installs `composer-projects/demo-app/composer.lock`; its path repository exposes the sibling Conjin package through `vendor/lucques/conjin`.
4. **Install the integration-test app:** Composer independently installs `composer-projects/integration-test-app/composer.lock`, keeping the automated fixture separate from the demo.
5. **Install locked browser and test dependencies:** `npm ci --ignore-scripts --omit=optional` recreates the repository-level `node_modules` from `package-lock.json`, including the integration-test workspace, without running package lifecycle scripts or installing optional Node-only tooling.
6. **Build pinned Dhall dependencies:** `composer-projects/conjin/deployment/dhall/vendor/build` downloads checksum-pinned Dhall source archives and resolves their remote imports.
7. **Build shared-module assets:** Every `composer-projects/conjin/ext/modules-shared/*/build` script copies only the files used by its Conjin module into the expected `res`, `scss`, or `src` folder.
8. **Configure application deployment paths:** The script writes ignored, machine-specific path files for both applications, the integration-test source path for the fixture, and the standard rclone configuration path for the demo (`$XDG_CONFIG_HOME/rclone/rclone.conf`, or `$HOME/.config/rclone/rclone.conf`). Dhall reads these files directly, so the configs also work in editor integrations without environment variables.

The module asset folders are generated outputs. Do not edit them directly. Public browser dependencies used to build those assets are declared in the root `package.json`. Framework PHP dependencies are declared in `composer-projects/conjin/composer.json`, while applications such as the demo app declare their dependency on Conjin in their own `composer.json`.

The root `.npmrc` also enforces disabled lifecycle scripts and omission of optional Node-only tooling for direct npm commands.

The private eskuel-suite and Tau Prolog interface bundles are stored as local npm tarballs in `composer-projects/conjin/ext/npm-packages`. Their lockfile entries provide the same integrity checking as registry packages. See that directory's README when updating them.

GeoGebra and Dhall are the exceptions because no suitable npm packages contain the required distributions. Their build scripts use immutable URLs and SHA-256 checksums.

Run setup before creating a Composer distribution archive so the archive contains the generated external-module assets and pinned Dhall sources. Consumers of a prepared distribution do not run these repository build steps after `composer install`.
