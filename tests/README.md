# Tests

Use `bin/test` from the repository root: `bin/test list`, `bin/test prepare [target]` or `bin/test <target> [path]`. The list displays categories, tools, components and prerequisites; `tests/suites.json` provides the machine-readable registry. Category selectors are `unit`, `component`, `integration`, `e2e`, `smoke` and `repository`. `fast` and `all` are execution profiles, and `php`/`python` are language selectors.

PHP tests use PHPUnit in Docker, Python tests use unittest, and browser/HTTP specs use Playwright in Docker. `bin/test prepare php` prepares the independent PHP runtime and locked development dependencies; `bin/test php` then runs without host PHP or prepared CSS/deployments. Component browsers reuse the deployed tests' Playwright image and Compose service, with PHP-rendered fixtures and no application deployment or host browser. Explicit `bin/test prepare` prepares all PHP/deployment/browser inputs; ordinary tests reuse them. Every selected suite runs. Each invocation replaces root `test-artifacts/` and writes combined JSON/JUnit plus native reports.

See [test documentation](../doc/tests.md) for setup, authoring, selection, lifecycle and reporting, and the generated [suite inventory and module coverage map](../doc/test_inventory.md) for coverage and known gaps.

PHP and Python files are discovered inside their category directories: `tests/php/{unit,integration}/` and `tests/python/{unit,integration}/`. The suite targets are `unit-phpunit`, `integration-phpunit`, `unit-unittest` and `integration-unittest`; each invokes its framework once. Adding a test file needs no new runner entry. Language and suite targets accept file filters, for example `bin/test php JSXGraphAxesTest.php`.
