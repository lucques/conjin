# Tests

`bin/test` is the single test entrypoint. It selects tests, checks prerequisites, manages isolated deployments and combines framework reports. PHPUnit, standard-library unittest and Playwright own assertions, case execution and their native reports. The machine-readable registry is [`tests/suites.json`](../tests/suites.json); the generated [inventory and module coverage map](test_inventory.md) documents every registered suite and the limits of its coverage.

The interface has three forms:

```sh
bin/test list
bin/test prepare [target]
bin/test <target> [path]
```

`list` shows available suites. `prepare` prepares the chosen target, or everything when omitted. A test target selects all tests, a profile, a category, a language or an individual suite. A path narrows a language or suite to matching files, or a crawl to a content location.

## Categories and profiles

Test categories describe the boundary being exercised, independently of the language or tool:

| Category | Boundary | Examples |
| --- | --- | --- |
| `unit` | Logic with controlled inputs, without real databases, filesystem operations or services in the behavior under test | Axis configuration, invalid form configuration, directory-name validation |
| `component` | One rendered component with controlled surroundings | Accordion and feedback browser fixtures |
| `integration` | Real interactions between modules, filesystem, SQLite, deployment tools or HTTP services | Module dispatch, resource containment, notifications, Dhall/rclone, Apache layouts and deployed HTTP contracts |
| `e2e` | Browser behavior through the deployed application | Module interactions and browser flows |
| `smoke` | Content and resource availability | Demo and integration LinkChecker crawls |
| `repository` | Repository consistency | License inventory and test documentation |

Top-level PHP and Python targets group whole categories, like the Playwright targets. Each selected category uses one framework invocation; individual files and cases remain visible in native and combined reports.

| Target | Automatically discovered files |
| --- | --- |
| `unit-phpunit` | `tests/php/unit/*Test.php` |
| `integration-phpunit` | `tests/php/integration/*Test.php` |
| `unit-unittest` | `tests/python/unit/test_*.py` |
| `integration-unittest` | `tests/python/integration/test_*.py` |

`fast` is an execution profile containing repository checks and Python/PHP suites; it includes both unit and integration tests. Its PHP suites use Docker, without browsers or deployed services. `all` selects every suite exactly once. `php` and `python` select languages. The runner's own tests are classified as integration because they exercise real temporary files and locks alongside mocked subprocesses. Running a unit test in Docker does not change its category.

```sh
bin/test unit
bin/test fast
bin/test all
bin/test php feedback_collector
bin/test php JSXGraphAxesTest.php
bin/test integration-phpunit
bin/test unit-unittest
bin/test python test_directory_validation.py
bin/test component-playwright feedback-collector.spec.ts
bin/test integration-http http.spec.ts
bin/test e2e-playwright bootstrap-alternative.spec.ts
bin/test demo-linkchecker bootstrap-alternative
```

PHP/Python language and suite filters match file paths relative to `tests/`, ignoring case and underscores. A filter may match several files; each affected category still runs its framework once. For example, `bin/test php feedback` selects the feedback unit and integration files. Playwright filters match spec paths. LinkChecker accepts a content path. Unknown targets, test files outside registered category directories and empty test selections fail rather than silently reducing coverage. Every selected suite runs; `all` always means all suites.

## Prerequisites and preparation

Run `./setup.sh` to install locked dependencies and generated assets; its dependency step calls `bin/test prepare php`. Python tests use standard-library unittest; pytest is not required. PHPUnit 12.5 is a locked Conjin development dependency compatible with the project's PHP 8.3 minimum. PHPUnit and component fixture rendering always use an isolated Docker PHP runtime, so they do not require a host PHP installation. Repository setup and deployment tooling still use host PHP/Composer for application dependencies.

For PHP tests alone, run `bin/test prepare php` followed by `bin/test php`; full repository setup is unnecessary. This prepares the PHP image and installs the framework's locked Composer development dependencies, checking the actual container platform. It requires Python and Docker, without Dhall, Apache startup, MariaDB, Sass, Node or Chromium. It does not compile CSS or prepare an application deployment.

The `php-test` target in the shared [server Dockerfile](../composer-projects/conjin/deployment/docker/images/conjin-server/Dockerfile) inherits the deployment PHP base and common extensions. The PHP image reference comes from `imagePHP` in [docker-images.dhall](../composer-projects/conjin/deployment/dhall/docker-images.dhall). The base includes Apache binaries, but the test target runs only CLI processes, before any application server configuration or volumes. Test-only DOM/XML/XMLWriter/mbstring/PDO SQLite support and Composer are added in this target; the default server target excludes those additions. Prepared state records the exact built image ID. Update the versioned PHP/Composer image references deliberately, then prepare again.

The runner prints the Docker PHP version and image and records them in each affected suite's report. Short-tag and assertion flags also apply to PHPUnit's isolated child processes.

Browser tests run Node, the locked Playwright package and Chromium inside Docker. Component, HTTP and end-to-end tests use the same prepared Playwright image and Compose service, defined by [`tests/integration/Dockerfile`](../tests/integration/Dockerfile) and the versioned `imagePlaywright` base. Its version must match the locked Playwright package. No host browser, browser cache or browser system packages are installed or used. Host Node/npm remain setup tools for generating application assets. Deployment tests need Docker and its Compose plugin. Dhall/rclone contracts need `dhall-to-json` and `rclone`. `bin/test list` shows suite-specific requirements.

```sh
bin/test prepare
bin/test prepare php
bin/test prepare e2e-playwright
bin/test prepare component-playwright
```

Preparation acts on the selected suites: PHP runtime/dependencies for PHP and component tests, deployment configuration/images/CSS for deployed tests, and those same integration image/CSS inputs for component tests. There is no separate component browser image, preparation step or cache. The browser image already contains Chromium and its system dependencies; preparation installs the locked Playwright npm package inside the image. State, Compose files and the component CSS snapshot live in `.cache/test-runner/`. `BOOTSTRAP_EXTENDED_CSS` can select an existing compiled extended-module stylesheet; the runner mounts it read-only into the component browser container.

Preparation announces each environment before starting, with its position in the queue and log path. Live messages identify prerequisite checks, configuration generation, Docker image downloads/builds, Composer installation, each CSS compilation and the shared Playwright image preparation. Each environment finishes with its status and elapsed time, followed by an overall summary. Detailed tool output remains in the indicated logs under `test-artifacts/prepare/`.

Ordinary execution does not build, pull, install dependencies, generate deployments or install browsers. Missing or outdated prerequisites block affected suites. PHP preparation fingerprints cover its Dockerfile, shared PHP image reference and framework Composer manifests; changes to CSS, application sources, test cases and unrelated image references do not invalidate it. Deployment fingerprints separately cover generators/configuration, image inputs, dependency locks and SCSS. PHP, content and spec changes use live mounts. Re-run preparation after changing its inputs. A missing PHP runtime reports `blocked` with `bin/test prepare php` as the remedy; Docker itself must also be accessible.

## Test layout and isolation

The runner starts one PHP worker container when first needed and reuses it for PHPUnit invocations and component fixture rendering during that invocation. PHPUnit's separate-process cases still launch fresh PHP child processes inside it. The worker has no network, mounts source read-only and writes only to its temporary filesystem, `test-artifacts/` and `.cache/phpunit/`, using the invoking user's UID/GID. Cleanup removes this worker even on test failure or interruption; `CONJIN_TEST_KEEP` applies only to application deployments. Image and dependency preparation caches survive. Composer installation alone receives network access and a writable framework vendor directory.

Before component browser execution, the runner renders the three PHP fixture variants in fresh PHP processes and saves their HTML in `test-artifacts/component-playwright/fixtures.json`. Playwright uses the existing integration test service with those fixtures, compiled CSS and source assets mounted from the checkout. `compose run --no-deps` starts only the test runner, using the image's Node/Playwright/browser installation. Each case receives a fresh browser page and fixture HTML. No Apache/MariaDB deployment starts for component tests. Reports are written as the invoking user. Each Playwright invocation removes its container; the existing project cleanup also handles failures and interruptions.

- `tests/php/unit/` contains pure PHP logic cases; `tests/php/integration/` contains SQLite, filesystem and module integration cases. Configuration is in `tests/php/phpunit.xml`.
- `tests/php/fixtures/` contains reusable test setup and captured service replacements. Cases using global function/class replacements run in separate PHP processes with global-state preservation disabled. Each case owns its temporary database/files; no real mail is sent.
- `tests/python/unit/` contains Python logic cases; `tests/python/integration/` contains runner, Dhall and rclone contracts. Upload checks use temporary local endpoints. Tests that need Dhall or rclone report missing tools individually, allowing other cases in the category to execute; missing coverage still makes the suite nonzero.
- `tests/components/` contains isolated Playwright browser cases. The runner renders PHP HTML once per fixture variant before Playwright starts. Every case gets a new page; accordion fragment navigation uses a fresh document when resetting a scenario. Native smooth scrolling is disabled in the fixture while collapse transitions remain active.
- `tests/integration/specs/http/` contains deployed HTTP contracts; `specs/e2e/` contains deployed browser specs. Each retains `expect-success` and `expect-fail` fixture branches. Expected error behavior is asserted by passing tests, not by marking tests as expected failures.
- `tests/check_private_layout_http.py` owns three Apache layout cases, each with many HTTP assertions. Cases report and clean up separately.
- `tests/support/` contains reporting and inventory adapters. These helpers are called by the runner and are not additional test entrypoints.

## Adding tests

Choose the narrowest boundary that exercises the intended behavior. Give each independent behavior a named case with fresh setup; use PHPUnit data providers for independent input cases. Preserve real dependency interactions when those interactions are the subject of the test. Fixture rendering belongs in a fixture helper, not behind an option that executes an assertion suite.

Add PHP `*Test.php` files or Python `test_*.py` files inside the appropriate language/category directory. No runner entry is needed for a new file. PHP uses the category suites in `tests/php/phpunit.xml`; Python uses standard unittest discovery. Nested Python directories must be importable packages with `__init__.py`. Add browser cases under the appropriate component, HTTP or end-to-end directory; Playwright discovers them automatically. Keep fixtures outside test discovery patterns.

`tests/suites.json` describes whole suites and their requirements. For dedicated PHP module tests, add a PHPUnit class attribute such as `#[\PHPUnit\Framework\Attributes\Group('module:jsxgraph-helpers')]`; the generated coverage map reads these labels without making the file a separate runner target. Regenerate the inventory when adding files or changing labels. Update suite-level components and requirements when introducing a new dependency or area of coverage.

Run the relevant target, then regenerate documentation with `python3 tests/support/document_inventory.py`. The repository check verifies that the generated inventory matches the registry and module/spec layout. Review the coverage map when extending a module: a mapped file proves only the behaviors asserted in its cases. Code coverage measurement and additional browser engines remain explicit follow-up work.

## Deployment lifecycle

Demo and integration preparation use each application's `deployments/test` definition. Runtime Compose projects have unique names and own their mutable database, preprocessing and store volumes. Prepared CSS volumes are external and mounted read-only. The integration crawl, HTTP contracts and browser flows reuse one deployment and one preprocessing step in an `all` run.

Preprocessing uses the configured internal endpoint and user. Browser requests use the public endpoint. Failed setup blocks dependent suites while independent suites continue. Apache layout cases use the prepared image/configuration in separate containers with networking disabled.

Cleanup removes only invocation-owned containers and mutable volumes. Prepared CSS survives; the shared Conjin reverse proxy remains running. `CONJIN_TEST_KEEP=1` retains runtime projects and prints their names. `CONJIN_TEST_PROJECT` selects their project-name prefix. Checkout locking prevents concurrent invocations from overwriting one another's reports.

## Reports and exit status

Every invocation replaces repository-root `test-artifacts/`, without timestamped directories. Preparation state survives separately. Reports include:

- `invocation.json`: exact command, working directory and start-time metadata.
- `summary.json`: suite metadata, prerequisites, status, individual cases, durations, failures, skips, flaky results/retry counts where provided, aggregate case counts and cleanup errors.
- `junit.xml`: combined results for CI; native PHPUnit XML and Playwright JSON/HTML/JUnit reports remain alongside suite logs.
- `component-playwright/`, `integration-http/`, `e2e-playwright/`: browser/HTTP results, including rendered component fixtures, failure screenshots and retained traces where applicable. Component suite metadata records both PHP and browser Docker images.
- `linkchecker/demo.html` and `linkchecker/integration.html`: stable crawl reports.
- `python/`, `php/`, `http-layouts/`, `repository-checks/` and `cleanup.log`: other native results and execution logs.

Assertions are not counted as separate test cases. A crawl or repository check appears as a suite-level result; the three Apache layouts are three cases. Unrun/blocked suites remain visible even when no native report exists. A failed case, missing coverage, implicit skip, flaky retry or cleanup failure produces a nonzero exit status; an interrupted invocation returns 130. Playwright retries are enabled in CI for diagnosis, and passing on retry does not make the overall invocation green.

Both CI workflows install setup prerequisites, call `bin/test prepare`, run `bin/test all` with the same Docker PHP runtime used locally, and upload `test-artifacts/` even on failure. Host PHP remains available for repository setup and package tooling; PHP test extensions come from the test image. Independent suites still execute if preparation fails. Package assembly and publication remain separate steps after successful tests.
