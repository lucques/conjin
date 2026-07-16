# Tests

Conjin's repository-level tests use one `TestDepl` named `test` in the demo app and one in the integration-test app. The global runner selects which capability to execute against each deployment.

## Prerequisites

Run `./setup.sh` once before running tests. Setup installs the locked dependencies, downloads the pinned Dhall dependencies, and writes the ignored machine-specific paths used by both test deployments.

The runner requires Python 3, a reachable Docker daemon, Docker 26 or newer, and the Docker Compose plugin. Playwright and LinkChecker run in containers.

## Test targets

Run commands from the repository root:

```sh
./tests/run demo-linkchecker
./tests/run integration-linkchecker
./tests/run integration-playwright
./tests/run all
```

`all` runs the targets in the order shown above. Each target gets an independent deployment attempt, and later targets still run when an earlier target fails. After all targets have run, the command exits with a failure status if any target failed.

Pass a target path to restrict a LinkChecker crawl:

```sh
./tests/run demo-linkchecker bootstrap-alternative
./tests/run integration-linkchecker module-tests-expect-success/db-mysql/query
```

Pass a path relative to `tests/integration/specs/` to restrict Playwright:

```sh
./tests/run integration-playwright /expect-fail
./tests/run integration-playwright /expect-success/module-tests/anchors.spec.ts
```

Pass `--skip-build` to reuse an existing generated test deployment during focused local runs:

```sh
./tests/run integration-playwright /expect-success/module-tests/anchors.spec.ts --skip-build
```

This skips only generation of `deployments/test/target/docker-compose-test.yml`. SCSS compilation, Docker service builds, preprocessing, isolated volumes, and cleanup still run normally. The generated deployment must already exist; rerun without `--skip-build` after changing deployment configuration, configured modules, SCSS compilation settings, or deployment-generation code.

The integration LinkChecker crawl excludes the intentionally failing `module-tests-expect-fail` content branch. Playwright includes the specs in both `expect-success` and `expect-fail`.

## Lifecycle

For each selected application, the runner generates `deployments/test/target/docker-compose-test.yml`, compiles required SCSS, prepares fresh named volumes, starts MariaDB and Apache, waits for health checks, and invokes the selected test service. Preprocessing and LinkChecker use the internal Apache endpoint on port 8080 with their configured user names and no passwords; browser tests continue to use the normal endpoint on port 80. In `all` mode the demo deployment is cleaned up before the integration deployment starts; the integration deployment is reused for its LinkChecker and Playwright phases.

Read-only source inputs are bind-mounted. Mutable database, preprocessing, compiled-CSS, and store data live in project-scoped named volumes that are removed after the run.

## Test organization

The Playwright configuration is in `tests/integration/playwright.config.ts`. Successful-contract specs live in `tests/integration/specs/expect-success/`; expected-failure specs live in `tests/integration/specs/expect-fail/`. Shared fixtures and assertions live in `tests/integration/fixtures/` and `tests/integration/lib/`, while `tests/integration/setup/preprocess.setup.ts` prepares generated application state.

## Reports and debugging

Every invocation creates a new timestamped directory such as `tests/artifacts/2026-07-23.12-23/`. Its `invocation.json` records the exact arguments, shell-safe command line, working directory, and timezone-aware start time. LinkChecker reports are written to its `linkchecker/` subdirectory as `demo.html` or `integration.html` and list successful URLs as well as warnings and errors. Playwright writes its HTML report, JUnit report, traces, and screenshots to `playwright/`. A numeric suffix is added if multiple invocations begin during the same minute.

Set `CONJIN_TEST_KEEP=1` to retain a Compose stack and its volumes. Set `CONJIN_TEST_PROJECT` to choose the Compose project-name prefix:

```sh
CONJIN_TEST_PROJECT=conjin-debug CONJIN_TEST_KEEP=1 ./tests/run integration-playwright /expect-fail
```

## Browser inspection

The integration-test app also has the persistent `conjin-test-web` deployment for manual fixture inspection. Build and start it:

```sh
./composer-projects/integration-test-app/deployments/conjin-test-web/build
./composer-projects/integration-test-app/deployments/conjin-test-web/target/bin/up
```

The fixture is available at `http://conjin-test-web.localhost/`. Run `target/bin/preprocess` after preprocessing-related changes and `target/bin/down` when finished.
