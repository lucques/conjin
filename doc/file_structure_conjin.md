# Conjin repository structure

The repository is a monorepo. All Composer roots are grouped below `composer-projects/`, while the repository root owns development-only setup, documentation, integration specifications, and supporting infrastructure.

```
- composer-projects
    - conjin                   Publishable `lucques/conjin` Composer package
        - deployment           Deployment command, Python implementation, Dhall, and Docker images
            - dhall/vendor     Pinned third-party Dhall sources
            - docker/images    Docker image definitions used by generated deployments
        - ext
            - modules-shared   Shared modules built from external packages or archives
            - npm-packages     Private npm package archives used to build module assets
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
- docker/docker-compose-setups Development infrastructure not shipped with the package
- setup.sh                     Installs dependencies, builds package assets, and configures the demo app
- package.json                 Build-time browser dependencies for external shared modules
- package-lock.json            Locked build-time browser dependency graph
```

Neither application resolves framework files directly through its parent. Both access Conjin through `vendor/lucques/conjin`; the path points to a symlink in this checkout and to installed package files for normal consumers.
