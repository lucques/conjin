# Conjin framework package

Conjin is a PHP framework for publishing programmable web documents. This directory is the `lucques/conjin` Composer package and contains the PHP runtime, internal and externally sourced shared modules, and the deployment generator.

Applications require this package in their own `composer.json` and invoke the installed deployment command through `vendor/bin/depl`. Conjin deliberately exposes its legacy PHP runtime functions in the global scope; Composer installs and versions the files but does not replace Conjin's existing module-loading mechanism.

This package is maintained inside the parent Conjin monorepo. Repository setup, asset builds, the reference application, tests, and complete documentation live at the repository root.
