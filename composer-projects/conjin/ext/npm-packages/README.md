# Local npm packages

This directory contains local npm packages for browser bundles built by private source repositories. `npm ci` installs and integrity-checks them together with public dependencies.

## Rebuilding

Run `./rebuild-private-packages` after rebuilding eskuel-suite or the Tau Prolog interface. It creates `conjin-eskuel-suite-assets-1.3.4.tgz` and `conjin-tau-prolog-interface-assets-0.0.1.tgz`, then run `npm install --package-lock-only` from the repository root to update their lockfile integrity values.

The script reads the built eskuel-suite files from `${project_dir}/priv/eskuel-suite/dist` and the Tau Prolog interface from `${project_dir}/priv/tau-prolog-interface/dist` by default, where `${project_dir}` is the parent of the Conjin checkout's `pub` directory. Override these paths with `ESKUEL_CURRENT_DIR` and `TAU_INTERFACE_DIR` when necessary.

The eskuel package contains its runtime chunks, assets, and `eskuel-suite.js`. It excludes source maps because Conjin does not serve them. The Tau Prolog package contains only `tau-prolog-interface.js`.
