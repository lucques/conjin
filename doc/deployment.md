# Deployment

This document describes how to build and run a concrete deployment of the Conjin framework. Before creating a deployment, complete the framework [setup](./setup.md).

There are two established deployment scenarios, both using Docker:

1. [Local: Docker + Nginx](./deployment_local.md)
2. [Remote: Docker + Sync](./deployment_remote.md)

## Building a deployment

Both scenarios use a very similar build process with similar outcomes. Overview:

- Run the installed `vendor/bin/depl build <local|remote|test> <config.dhall>` command to build a deployment. In this repository, the applications below `composer-projects/` provide small wrappers for their configured deployment types.
- This will...
    - Populate the `target` dir within the deployment dir. Some are only present in certain types of deployment.
        - Config files for docker-compose etc.
            - `docker-compose.yml`
        - Bin files to run the app
            - `bin/down`: Stops the web app
            - `bin/linkchecker`: Runs the linkchecker
            - `bin/preprocess`: Runs the preprocessing phase
            - `bin/upload`: Uploads to the remote server
            - `bin/up`: Starts the web app
            - ...
            - See also [deployment_local.md](./deployment_local.md) and [deployment_remote](./deployment_remote.md)
        - Config files that are used by webserver
            - `.htaccess`
            - `config.json`
            - `users.json`
    - Optionally (through flags) perform checks and integrate with the environment.
        - Composer checks that the installed Conjin package satisfies the application's declared version constraint before deployment generation.
        - Create dirs for docker volumes, typically (but not necessarily at these locations):
            - `docker_volumes/css`
            - `docker_volumes/linkchecker`
            - `docker_volumes/preprocess`
            - `docker_volumes/store`
                - See [Store](./store.md) for persistence, configuration, and backups.
            - `docker_volumes/logs/error`
                - See [Error handling](./error_handling.md) for logging configuration and persistence.
        - Check that all volume source paths exist
        - For remote deployments, register passwords in the desktop via the scripts given in the config. Local preprocessing and link checking use the trusted internal Compose endpoint described in [deployment_local.md](./deployment_local.md#internal-job-authentication).
            - Example: Store password of the preprocessing user via `secret-tool store --label='dummy-preprocess' password dummy-preprocess`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
        - Install symlinks to the generated scripts in `~/bin`
        - Force build of the Docker images

- Building does not...
    - Render SCSS files
    - Preprocess
