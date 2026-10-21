# Changelog

## Unreleased

- Removed automatic `content.php` rendering and `ContentLocation::EXTRA`. Move each content body into a `$process = function (Target $target) { ... };` closure in the target's `index.php`, then remove `content.php`. A target without its own `$preprocess` still uses `system/target_default.php`. Rerun preprocessing after upgrading to refresh cached targets and navigation; old cached `EXTRA` targets are incompatible with the removed enum case.
- Modules now expose only `$preprocess_target` / `$preprocess_syslet` in `preprocessing.php` and `$process_target` / `$process_syslet` in `processing.php`. Rename the previous type-specific hooks and replace common hooks with local closure sharing or explicit helper calls. The corresponding `Module` methods use these new names too; old hook names are no longer dispatched. Content and system `$preprocess` / `$process` closures are unchanged. See [module entrypoints](modules.md#entrypoints-and-local-code-sharing).

## [3.0.0] - 2026-09-06

- Replace flat JSXGraph axis, tick, spacing, and grid arguments with typed configurations shared by Cartesian, sequence, dynamic 3D, and cavalier plots. Axis visibility and tick appearance inherit independently of coordinate spacing. Existing callers must migrate to the new API; see the [migration table](../composer-projects/conjin/src/modules-shared/jsxgraph-helpers/README.md#migrating-from-conjin-2x).

## [2.8.0] - 2026-07-30
- Restructured the repository; added demo-app and tests

## [2.7.0] - 2026-07-16
- Updated many modules and more

## [2.6.0] - 2025-08-19
- Updated template mechnanism, added `issue-tracker` module

## [2.5.0] - 2025-08-07
- Updated many components, modules and more

## [2.4.0] - 2024-10-02
- Reworked `timetable` module
- Many small fixes

## [2.3.0] - 2024-10-02
- Targets may now be written hard-coded into `content.php`

## [2.2.0] - 2024-08-21
- Refactored password config mechanism

## [2.1.0] - 2024-08-21
- Refactored module config mechanism

## [2.0.1] - 2024-08-20
- Updated server config mechanism

## [2.0.0] - 2024-08-19
- Major overhaul of everything, reorganized templating mechanism and much more

## [1.2.0] - 2024-04-21
- Update Dhall config mechanism

## [1.1.0] - 2024-03-26
- Reorganized the authorization mechanism
- Reorganized how the nav is rendered
- Improved documentation
