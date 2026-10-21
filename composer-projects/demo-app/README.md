# Conjin demo app

This application demonstrates Conjin features and modules as independently as practical. It is intended for people exploring the framework rather than as an automated-test fixture. A deployed version is available at [https://lukas.convnet.de/conjin-demo/](https://lukas.convnet.de/conjin-demo/).

## Setup

Run `./setup.sh` from the repository root. Composer installs the sibling Conjin path package under `composer-projects/demo-app/vendor/lucques/conjin`, and setup creates the ignored local path files used by the app's Dhall configuration. Run setup again after moving the checkout.

## Local deployment

Build the local demo deployment with `./composer-projects/demo-app/deployments/lcd/build`. Its generated scripts are written to `composer-projects/demo-app/deployments/lcd/target/bin`; run `up`, `preprocess`, or `linkchecker` from there as needed. LinkChecker provides an independent whole-demo crawl rather than being part of the focused integration fixture.

## Positive test suite

Run `bin/test prepare demo-linkchecker` once to prepare the isolated `deployments/test` environment. `bin/test demo-linkchecker` then preprocesses and crawls the demo tree using its prepared images and CSS. `bin/test all` includes this crawl alongside all other test suites. The command fails when an internal target or resource cannot be retrieved successfully and writes `test-artifacts/linkchecker/demo.html`, replacing reports from the previous invocation.

Each run uses fresh Docker Compose volumes for mutable data and removes them afterward; prepared CSS volumes remain reusable. The `CONJIN_TEST_KEEP` and `CONJIN_TEST_PROJECT` controls apply to both test deployments.

## License

This project is licensed under GPLv3.
