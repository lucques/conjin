# Composer package distribution

The `Build Composer package` GitHub Actions workflow creates a versioned ZIP for manual distribution. A `workflow_dispatch` run builds and uploads the ZIP without publishing it. Pushing a tag such as `v2.8.0` additionally publishes the assembled package to [lucques/conjin-package](https://github.com/lucques/conjin-package). Tag-triggered builds fail when the tag does not match the `version` in `composer-projects/conjin/composer.json`.

The workflow installs locked dependencies, builds generated assets, checks the dependency-license inventory, runs the complete test suite, assembles `dist/lucques-conjin`, validates its `composer.json`, and uploads `lucques-conjin-VERSION.zip` together with its SHA-256 checksum. The package ZIP has `composer.json` at its root, as required by Composer's `artifact` repository type. The ZIP retains the explicit `version` field because no package-repository tag is available to Composer after manual download.

For repository publication, the workflow clones the package repository's `main` branch, replaces its contents with the assembled package, removes the explicit `version` field from the published `composer.json`, commits the snapshot, creates the matching annotated version tag, and atomically pushes the branch and tag. The commit and tag record the source repository commit. Existing publication tags cause the workflow to fail instead of rewriting an immutable release.

## Publication authentication

Create a dedicated Ed25519 key pair for this one repository. Add the public key to `lucques/conjin-package` under **Settings → Deploy keys**, enable **Allow write access**, and add the private key to this development repository under **Settings → Secrets and variables → Actions** as the repository secret `CONJIN_PACKAGE_DEPLOY_KEY`. The key must not require a passphrase because the workflow runs non-interactively. Do not reuse a personal SSH key.

After the first successful tagged publication, submit `https://github.com/lucques/conjin-package` to Packagist and enable its GitHub webhook. Packagist derives stable versions from the repository tags; the published `composer.json` therefore intentionally omits its `version` field.

## Manual artifact installation

After downloading the workflow artifact, extract the outer GitHub artifact ZIP and place the inner package ZIP in an application's `packages/` directory. Configure the application as follows:

```json
{
    "repositories": [
        {
            "type": "artifact",
            "url": "packages"
        }
    ],
    "require": {
        "lucques/conjin": "^2.8"
    }
}
```

Run `composer update lucques/conjin` once to select the downloaded version and commit the resulting `composer.lock`. Later deployments use `composer install`. Treat published package ZIPs as immutable: create a new version rather than replacing an archive that applications may already have locked.
