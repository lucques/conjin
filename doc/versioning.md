# Versioning

Conjin and applications use Composer's version model, which is based on [SemVer](https://semver.org/).

- **Actual Conjin version:** During development as a local Composer path repository, the package version is stored in the `version` field of [composer-projects/conjin/composer.json](../composer-projects/conjin/composer.json). Published package versions should be derived from version-control tags by the package repository.
- **Required Conjin version:** An application expresses its compatible range in its Composer requirement, for example `"lucques/conjin": "^2.7"` in [composer-projects/demo-app/composer.json](../composer-projects/demo-app/composer.json).
- **Installed Conjin version:** The application's `composer.lock` records the exact resolved version. `composer install` reproduces it, and Composer rejects incompatible constraints before deployment generation is run.

An application can be independently versioned with normal project tags or release metadata; it does not need a Conjin-specific metadata file.
