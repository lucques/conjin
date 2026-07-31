# Conjin repository structure

The repository is a monorepo. All Composer roots are grouped below `composer-projects/`, while the repository root owns development-only setup, documentation, integration specifications, and supporting infrastructure.

```
- composer-projects
    - conjin                   Publishable `lucques/conjin` Composer package
        - deployment           Deployment command, Python implementation, Dhall, and Docker images
            - docker/images    Docker image definitions used by generated deployments
        - ext
            - dhall            Pinned third-party Dhall sources
            - modules-shared   Shared modules built from external packages or archives
        - src
            - conjin           Framework runtime mounted at `htdocs/conjin`
            - modules-shared   Internally maintained shared modules
            - htaccess         Components used to generate the root `.htaccess`
        - composer.json        Framework package metadata and runtime requirements
        - composer.lock        Development dependency lock file
    - demo-app                 Human-facing module and template demonstrations
    - integration-test-app     Minimal deterministic fixture for automated tests
- tests                        Repository-level integration test runner and specifications
- doc                          Repository and framework documentation
- build-assets.sh              Builds generated npm and Dhall assets in the development tree
- build-composer-package.sh    Assembles source and generated assets under dist/lucques-conjin
- publish-composer-package.sh  Publishes an assembled release to the package repository
- DEPENDENCY_LICENSES.md       Generated inventory of locked dependency licenses
- licenses/                    License inventory and legal-file collection tools
- dist/lucques-conjin          Ignored clean staging tree for the distributable Composer package
- setup.sh                     Installs dependencies, builds package assets, and configures the applications
- package.json                 Build-time browser dependencies for external shared modules
- package-lock.json            Locked build-time browser dependency graph
```

Neither application resolves framework files directly through its parent. Both access Conjin through `vendor/lucques/conjin`; the path points to a symlink in this checkout and to installed package files for normal consumers.
