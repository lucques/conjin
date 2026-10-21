# Test inventory and coverage map

Generated from `tests/suites.json`, test directories, PHP module groups and deployed spec filenames by `python3 tests/support/document_inventory.py`. The repository-check suite detects stale documentation.

## Registered suites

| Suite | Category | Tool | Components | Prerequisites | Coverage |
| --- | --- | --- | --- | --- | --- |
| `repository-checks` | repository | repository checks | dependencies | Python 3 | Dependency-license inventory |
| `unit-unittest` | unit | unittest | deployment | Python 3 | Python logic and validation |
| `integration-unittest` | integration | unittest | deployment, test-runner | Python 3, dhall-to-json, rclone | Python filesystem, runner and deployment contracts |
| `unit-phpunit` | unit | PHPUnit | feedback-collector, jsxgraph-helpers | Python 3, Docker + prepared PHP runtime, PHPUnit 12.5 (prepared) | PHP configuration and value logic |
| `integration-phpunit` | integration | PHPUnit | bootstrap-extended, feedback-collector, issue-tracker, module-dispatch, resource-resolver, template-interbook | Python 3, Docker + prepared PHP runtime, PHPUnit 12.5 (prepared) | PHP modules, SQLite, filesystem and notification contracts |
| `component-playwright` | component | Playwright | bootstrap-extended, bootstrap-alternative, feedback-collector | Python 3, Docker + prepared PHP runtime, Docker Compose + shared prepared Playwright image, compiled Bootstrap CSS and JavaScript | Isolated PHP-rendered browser components |
| `http-layouts` | integration | Apache HTTP | deployment, authorization, resource-resolver | Python 3, docker, dhall-to-json, prepared integration environment | Apache/PHP in local, remote and nested layouts |
| `demo-linkchecker` | smoke | LinkChecker | demo-content | Python 3, docker, prepared demo environment | Demo content crawl |
| `integration-linkchecker` | smoke | LinkChecker | integration-content | Python 3, docker, prepared integration environment | Integration content crawl |
| `integration-http` | integration | Playwright | http-contracts | Python 3, docker, prepared integration environment | Deployed HTTP contracts and expected errors |
| `e2e-playwright` | e2e | Playwright | browser-flows | Python 3, docker, prepared integration environment | Deployed browser behavior and module flows |

## Discovered PHP and Python files

Frameworks discover these files automatically inside each category. File filters narrow a suite without creating another top-level target. PHP `Group('module:…')` attributes identify dedicated module coverage for the map below.

| Suite | File | Shared modules |
| --- | --- | --- |
| `unit-unittest` | [test_directory_validation.py](../tests/python/unit/test_directory_validation.py) | — |
| `integration-unittest` | [test_remote_internal.py](../tests/python/integration/test_remote_internal.py) | — |
| `integration-unittest` | [test_runner.py](../tests/python/integration/test_runner.py) | — |
| `unit-phpunit` | [FeedbackConfigurationTest.php](../tests/php/unit/FeedbackConfigurationTest.php) | feedback-collector |
| `unit-phpunit` | [JSXGraphAxesTest.php](../tests/php/unit/JSXGraphAxesTest.php) | jsxgraph-helpers |
| `unit-phpunit` | [LoginRedirectTest.php](../tests/php/unit/LoginRedirectTest.php) | — |
| `unit-phpunit` | [SubmissionTokensTest.php](../tests/php/unit/SubmissionTokensTest.php) | — |
| `unit-phpunit` | [TargetSplitTest.php](../tests/php/unit/TargetSplitTest.php) | — |
| `integration-phpunit` | [AuthSessionTest.php](../tests/php/integration/AuthSessionTest.php) | — |
| `integration-phpunit` | [FeedbackCollectorTest.php](../tests/php/integration/FeedbackCollectorTest.php) | feedback-collector |
| `integration-phpunit` | [IssueTrackerNotificationsTest.php](../tests/php/integration/IssueTrackerNotificationsTest.php) | issue-tracker |
| `integration-phpunit` | [ModuleEntrypointsTest.php](../tests/php/integration/ModuleEntrypointsTest.php) | bootstrap-extended, template-interbook |
| `integration-phpunit` | [PreprocessOutputTest.php](../tests/php/integration/PreprocessOutputTest.php) | — |
| `integration-phpunit` | [ResourceFilesTest.php](../tests/php/integration/ResourceFilesTest.php) | — |
| `integration-phpunit` | [TargetContentTest.php](../tests/php/integration/TargetContentTest.php) | — |

## Shared module map

This maps dedicated tests to modules; it is not a measured code-coverage percentage or a claim that every behavior is covered. A dash means no dedicated test is mapped at that level. LinkChecker may traverse demo or integration content for a module, but crawling alone does not establish its behavioral coverage. PHP integration cases use real SQLite/filesystem or module machinery with captured external effects. Component cases use routed browser fixtures; end-to-end cases use the deployed application.

| Module | Unit | Integration | Component | End-to-end |
| --- | --- | --- | --- | --- |
| anchors | — | [expect-fail/anchors.spec.ts](../tests/integration/specs/http/expect-fail/module-tests/anchors.spec.ts) | — | [expect-success/anchors.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/anchors.spec.ts) |
| bootstrap | — | — | — | [expect-success/bootstrap.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/bootstrap.spec.ts) |
| bootstrap-alternative | — | — | [component-playwright](../tests/components/bootstrap-accordion.spec.ts) | [expect-success/bootstrap-alternative.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/bootstrap-alternative.spec.ts) |
| bootstrap-extended | — | [ModuleEntrypointsTest.php](../tests/php/integration/ModuleEntrypointsTest.php) | [component-playwright](../tests/components/bootstrap-accordion.spec.ts) | — |
| bootstrap-icons | — | — | — | [expect-success/bootstrap-icons.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/bootstrap-icons.spec.ts) |
| chart-js | — | — | — | [expect-success/chart-js.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/chart-js.spec.ts) |
| db-mysql | — | [expect-success/db-mysql.spec.ts](../tests/integration/specs/http/expect-success/module-tests/db-mysql.spec.ts) | — | — |
| doc-extensions | — | — | — | [expect-success/doc-extensions.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/doc-extensions.spec.ts) |
| dynamic-toggle | — | — | — | [expect-success/dynamic-toggle.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/dynamic-toggle.spec.ts) |
| eskuel-suite | — | — | — | — |
| exercise | — | [expect-fail/exercise.spec.ts](../tests/integration/specs/http/expect-fail/module-tests/exercise.spec.ts) | — | [expect-success/exercise.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/exercise.spec.ts) |
| favicons | — | — | — | [expect-success/favicons.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/favicons.spec.ts) |
| feedback-collector | [FeedbackConfigurationTest.php](../tests/php/unit/FeedbackConfigurationTest.php) | [FeedbackCollectorTest.php](../tests/php/integration/FeedbackCollectorTest.php) | [component-playwright](../tests/components/feedback-collector.spec.ts) | — |
| footnotes | — | — | — | [expect-success/footnotes.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/footnotes.spec.ts) |
| fullcalendar | — | — | — | [expect-success/fullcalendar.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/fullcalendar.spec.ts) |
| geogebra | — | — | — | — |
| geogebra-helpers | — | — | — | — |
| grading | — | — | — | [expect-success/grading.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/grading.spec.ts) |
| graphing-types | — | — | — | — |
| hamster | — | — | — | — |
| html | — | — | — | — |
| issue-tracker | — | [IssueTrackerNotificationsTest.php](../tests/php/integration/IssueTrackerNotificationsTest.php)<br>[expect-fail/issue-tracker.spec.ts](../tests/integration/specs/http/expect-fail/module-tests/issue-tracker.spec.ts) | — | [expect-success/issue-tracker.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/issue-tracker.spec.ts) |
| java-worlds | — | — | — | — |
| js-standard-lib | — | — | — | [expect-success/js-standard-lib.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/js-standard-lib.spec.ts) |
| json-editor | — | — | — | — |
| jsxgraph | — | — | — | [expect-success/jsxgraph.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/jsxgraph.spec.ts) |
| jsxgraph-helpers | [JSXGraphAxesTest.php](../tests/php/unit/JSXGraphAxesTest.php) | [expect-fail/jsxgraph-helpers.spec.ts](../tests/integration/specs/http/expect-fail/module-tests/jsxgraph-helpers.spec.ts) | — | [expect-success/jsxgraph-axes.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/jsxgraph-axes.spec.ts)<br>[expect-success/jsxgraph-helpers.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/jsxgraph-helpers.spec.ts) |
| klipse-with-tau-prolog | — | — | — | — |
| locale-de | — | — | — | — |
| localization | — | [expect-success/localization.spec.ts](../tests/integration/specs/http/expect-success/module-tests/localization.spec.ts) | — | [expect-success/localization.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/localization.spec.ts) |
| markdown | — | — | — | [expect-success/markdown.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/markdown.spec.ts) |
| math-arith | — | — | — | — |
| math-logic | — | — | — | — |
| math-probability | — | — | — | — |
| math-value-table | — | — | — | [expect-success/math-value-table.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/math-value-table.spec.ts) |
| mathjax | — | — | — | [expect-success/mathjax.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/mathjax.spec.ts) |
| mathjax-extensions | — | — | — | [expect-success/mathjax-extensions.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/mathjax-extensions.spec.ts) |
| mathjs | — | — | — | [expect-success/mathjs.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/mathjs.spec.ts) |
| matomo-http-api | — | — | — | — |
| nav-build | — | — | — | — |
| nav-common | — | — | — | — |
| nav-view | — | — | — | [expect-success/nav-view.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/nav-view.spec.ts) |
| nerdamer | — | — | — | [expect-success/nerdamer.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/nerdamer.spec.ts) |
| nuggets | — | — | — | — |
| page-prep | — | — | — | — |
| paged-js | — | — | — | [expect-success/paged-js.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/paged-js.spec.ts) |
| picocss | — | — | — | — |
| print-mode | — | [expect-success/print-mode.spec.ts](../tests/integration/specs/http/expect-success/module-tests/print-mode.spec.ts) | — | [expect-success/print-mode.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/print-mode.spec.ts) |
| prism | — | — | — | [expect-success/prism.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/prism.spec.ts) |
| references | — | — | — | — |
| sol-mode | — | — | — | [expect-success/sol-mode.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/sol-mode.spec.ts) |
| source | — | — | — | [expect-success/source.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/source.spec.ts) |
| spinning-wheel | — | — | — | — |
| sql-js | — | — | — | — |
| sql-js-extensions | — | — | — | [expect-fail/sql-js-extensions.spec.ts](../tests/integration/specs/e2e/expect-fail/module-tests/sql-js-extensions.spec.ts)<br>[expect-success/sql-js-extensions.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/sql-js-extensions.spec.ts) |
| sql-js-knowledge-history | — | — | — | [expect-success/sql-js-knowledge-history.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/sql-js-knowledge-history.spec.ts) |
| sql-js-knowledge-tagged | — | — | — | [expect-success/sql-js-knowledge-tagged.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/sql-js-knowledge-tagged.spec.ts) |
| subpages-all | — | — | — | [expect-success/subpages-all.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/subpages-all.spec.ts) |
| sync-dims | — | — | — | [expect-success/sync-dims.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/sync-dims.spec.ts) |
| template-exam | — | — | — | — |
| template-generic | — | — | — | — |
| template-interbook | — | [ModuleEntrypointsTest.php](../tests/php/integration/ModuleEntrypointsTest.php) | — | — |
| template-navigable | — | — | — | — |
| timetable | — | — | — | [expect-success/timetable.spec.ts](../tests/integration/specs/e2e/expect-success/module-tests/timetable.spec.ts) |
| title | — | — | — | — |
| tom-select | — | — | — | [expect-success/tom-select.spec.ts](../tests/integration/specs/e2e/expect-success/module-ext-tests/tom-select.spec.ts) |

## Limits and follow-up work

- The map records dedicated suite/spec presence. Read each named case for the exact behavior asserted; an asset-loading test does not imply full module coverage.
- PHP line/branch coverage is not collected yet. Add an explicitly selected coverage run before setting coverage thresholds; avoid treating a percentage as evidence of correctness.
- Browser execution currently covers Chromium. Firefox/WebKit coverage should be added if those browsers are part of the supported product matrix.
- External mail delivery and remote uploads are replaced with captured mail and temporary local rclone endpoints. Their real service integrations require separately configured tests.
- Runner preparation is tested with fake subprocesses in the ordinary suite. A real `bin/test prepare` followed by `bin/test all` verifies Docker integration; CI performs both.
