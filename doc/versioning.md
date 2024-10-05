# Versioning

There is an extremely simple versioning system, all of them based on [SemVer](https://semver.org/).

- **Actual conjin version**: The version of the conjin software itself, i.e. this very repo, is stored in the `version` field of the [./composer.json](../composer.json) file.
- **Required conjin version**: The conjin version that is required for a content repo is stored in the `conjinVersion` field of the `metadata.json` file in the root of a content repo.

Optionally, also a content repo may be versioned by using the `version` field of the `metadata.json` file in the root of a content repo.