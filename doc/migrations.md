# Migrations

This document records temporary compatibility code and other migration steps
that must be removed after their rollout period.


## Active temporary migrations

### Unified remote Docker Compose file

Since July 21, 2026, remote deployments generate a single `target/docker-compose.yml` containing their upload, Sass compilation, and optional backup jobs. This replaces `docker-compose-upload.yml`, `docker-compose-upload-omit-sass.yml`, `docker-compose-backup-errorlog.yml`, and `docker-compose-backup-store.yml`.

During a remote deployment build, `composer-projects/conjin/deployment/remote.py` deletes these obsolete Compose files after generating the new artifacts. This prevents stale generated files from remaining in existing deployment targets and being mistaken for active configuration.

This cleanup is intentionally temporary and can be removed from `composer-projects/conjin/deployment/remote.py` once all maintained remote deployment targets have been regenerated with the unified Compose configuration.

### Unified local Docker Compose file

Since July 21, 2026, local deployments generate a single `target/docker-compose.yml` instead of separate `target/docker-compose-app.yml` and `target/docker-compose-linkchecker.yml` files.

During a local deployment build, `composer-projects/conjin/deployment/local.py` deletes the two obsolete Compose files after generating the new artifacts. This prevents stale generated files from remaining in existing deployment targets and being mistaken for active configuration.

This cleanup is intentionally temporary and can be removed from `composer-projects/conjin/deployment/local.py` once all maintained local deployment targets have been regenerated with the unified Compose configuration.

### Array-based session users to `ResolvedUser`

Since July 16, 2026, the authenticated user stored in `$_SESSION['user']` is a
`ResolvedUser` object instead of a Dhall-shaped associative array. Sessions
created by older versions therefore contain an incompatible value.

During `auth_init()`, Conjin checks an existing session user. If the value does
not implement `ResolvedUser`, it clears all session variables and regenerates
the session ID. Normal authentication initialization then creates a fresh guest
session, so affected users have to log in again. No old session data is
translated or retained.

This compatibility block is intentionally temporary and should be removed from
`composer-projects/conjin/src/conjin/inc/auth.php` after **August 15, 2026**.
