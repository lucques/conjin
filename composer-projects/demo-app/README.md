# Conjin demo app

This application demonstrates Conjin features and modules as independently as practical. It is intended for people exploring the framework rather than as an automated-test fixture. A deployed version is available at [https://lukas.convnet.de/conjin-demo/](https://lukas.convnet.de/conjin-demo/).

## Setup

Run `./setup.sh` from the repository root. Composer installs the sibling Conjin path package under `composer-projects/demo-app/vendor/lucques/conjin`, and setup creates the ignored local path files used by the app's Dhall configuration. Run setup again after moving the checkout.

## Local deployment

Build the local demo deployment with `./composer-projects/demo-app/deployments/lcd/build`. Its generated scripts are written to `composer-projects/demo-app/deployments/lcd/target/bin`; run `up`, `preprocess`, or `linkchecker` from there as needed. LinkChecker provides an independent whole-demo crawl rather than being part of the focused integration fixture.

## Positive test suite

The `./tests/run demo-linkchecker` target builds the isolated `deployments/test` deployment, preprocesses the demo tree, and crawls it with LinkChecker. `./tests/run all` runs this target before the integration-app targets. The command fails when an internal target or resource cannot be retrieved successfully and writes the demo report into the invocation's timestamped directory below `tests/artifacts/`.

Each run uses fresh Docker Compose named volumes and removes them afterward. The global `CONJIN_TEST_KEEP` and `CONJIN_TEST_PROJECT` controls apply to both test deployments.

## License

This project is licensed under GPLv3.
