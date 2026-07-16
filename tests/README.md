# Tests

The repository-level entry point is `./tests/run`. It operates the `deployments/test` deployment of the selected Conjin application and runs only the requested test capability.

The available targets are `demo-linkchecker`, `integration-linkchecker`, `integration-playwright`, and `all`. The `all` target runs the other three in that order, continues after individual target failures, and exits with a failure status after every target has been attempted if any target failed.

LinkChecker targets and Playwright suite paths can be selected by passing an optional second argument to a single target. Every invocation creates a timestamped directory below `tests/artifacts/`, with invocation provenance in `invocation.json` and results separated into `linkchecker/` and `playwright/`. LinkChecker HTML reports include successful URLs as well as warnings and errors.
