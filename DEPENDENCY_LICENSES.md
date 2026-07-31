# Dependency licenses

This generated inventory lists dependency licenses declared by the repository lockfiles. Regenerate it with `./licenses/generate-report`; use `./licenses/generate-report --check` in automation. Package metadata is evidence supplied by the package publisher, not a legal compatibility determination.

Conjin-authored code is licensed under `GPL-3.0-or-later`. Dependencies and separately distributed components remain under their own licenses. Redistribution-specific notices and the current release status are documented in [`composer-projects/conjin/THIRD_PARTY_NOTICES.md`](./composer-projects/conjin/THIRD_PARTY_NOTICES.md).

## Current release status

No unresolved licensing blocker is currently identified by this inventory. All locked Composer and npm packages declare licenses; the pinned `sbdchd/dhall-docker-compose` source declares MIT and retains its upstream license file; and legal files supplied with redistributed npm browser assets are collected into the assembled Composer package. This is an inventory-based compliance status, not a legal opinion.

## Composer dependencies

| Package | Version | Declared license | Lockfile projects |
| --- | --- | --- | --- |
| erusev/parsedown | 1.8.0 | MIT | conjin, demo-app, integration-test-app |
| jumbojett/openid-connect-php | v1.0.2 | Apache-2.0 | conjin, demo-app, integration-test-app |
| lucques/conjin | 2.7.0 | GPL-3.0-or-later | demo-app, integration-test-app |
| monolog/monolog | 3.10.0 | MIT | demo-app, integration-test-app |
| monolog/monolog | 3.9.0 | MIT | conjin |
| paragonie/constant_time_encoding | v3.1.3 | MIT | conjin, demo-app, integration-test-app |
| paragonie/random_compat | v9.99.100 | MIT | conjin, demo-app, integration-test-app |
| phpmailer/phpmailer | v6.10.0 | LGPL-2.1-only | conjin |
| phpmailer/phpmailer | v6.12.0 | LGPL-2.1-only | demo-app, integration-test-app |
| phpseclib/phpseclib | 3.0.55 | MIT | conjin, demo-app, integration-test-app |
| psr/log | 3.0.2 | MIT | conjin, demo-app, integration-test-app |

PHP and PHP extensions are platform requirements and are not Composer packages, so their licenses do not appear in Composer lockfile metadata.

## npm dependencies

| Package | Version | Declared license | Lockfile scope |
| --- | --- | --- | --- |
| @babel/polyfill | 7.12.1 | MIT | root npm lock |
| @babel/runtime | 7.29.7 | MIT | root npm lock |
| @fullcalendar/bootstrap5 | 6.1.15 | MIT | root npm lock |
| @fullcalendar/core | 6.1.21 | MIT | root npm lock |
| @fullcalendar/daygrid | 6.1.15 | MIT | root npm lock |
| @fullcalendar/daygrid | 6.1.21 | MIT | root npm lock |
| @fullcalendar/interaction | 6.1.21 | MIT | root npm lock |
| @fullcalendar/list | 6.1.21 | MIT | root npm lock |
| @fullcalendar/multimonth | 6.1.21 | MIT | root npm lock |
| @fullcalendar/timegrid | 6.1.21 | MIT | root npm lock |
| @kurkle/color | 0.3.4 | MIT | root npm lock |
| @mapbox/node-pre-gyp | 1.0.11 | BSD-3-Clause | root npm lock |
| @orchidjs/sifter | 1.1.0 | Apache-2.0 | root npm lock |
| @orchidjs/unicode-variants | 1.1.2 | Apache-2.0 | root npm lock |
| @picocss/pico | 2.0.6 | MIT | root npm lock |
| @playwright/test | 1.61.0 | Apache-2.0 | integration tests |
| @playwright/test | 1.61.0 | Apache-2.0 | root npm lock |
| @popperjs/core | 2.11.8 | MIT | root npm lock |
| abbrev | 1.1.1 | ISC | root npm lock |
| agent-base | 6.0.2 | MIT | root npm lock |
| ansi-regex | 5.0.1 | MIT | root npm lock |
| aproba | 2.1.0 | ISC | root npm lock |
| are-we-there-yet | 2.0.0 | ISC | root npm lock |
| balanced-match | 1.0.2 | MIT | root npm lock |
| bootstrap | 5.3.3 | MIT | root npm lock |
| bootstrap-icons | 1.11.3 | MIT | root npm lock |
| brace-expansion | 1.1.16 | MIT | root npm lock |
| canvas | 2.11.2 | MIT | root npm lock |
| chart.js | 4.5.1 | MIT | root npm lock |
| chownr | 2.0.0 | ISC | root npm lock |
| clear-cut | 2.0.2 | MIT | root npm lock |
| color-support | 1.1.3 | ISC | root npm lock |
| complex.js | 2.4.3 | MIT | root npm lock |
| concat-map | 0.0.1 | MIT | root npm lock |
| conjin-integration-tests | workspace | GPL-3.0-or-later | root npm lock |
| console-control-strings | 1.1.0 | ISC | root npm lock |
| core-js | 2.6.12 | MIT | root npm lock |
| css-tree | 1.1.3 | MIT | root npm lock |
| d | 1.0.2 | ISC | root npm lock |
| debug | 4.4.3 | MIT | root npm lock |
| decimal.js | 10.6.0 | MIT | root npm lock |
| decompress-response | 4.2.1 | MIT | root npm lock |
| delegates | 1.0.0 | MIT | root npm lock |
| detect-libc | 2.1.2 | Apache-2.0 | root npm lock |
| emoji-regex | 8.0.0 | MIT | root npm lock |
| es5-ext | 0.10.64 | ISC | root npm lock |
| es6-iterator | 2.0.3 | MIT | root npm lock |
| es6-symbol | 3.1.4 | ISC | root npm lock |
| escape-latex | 1.2.0 | MIT | root npm lock |
| esniff | 2.0.1 | ISC | root npm lock |
| event-emitter | 0.3.5 | MIT | root npm lock |
| ext | 1.7.0 | ISC | root npm lock |
| fraction.js | 4.3.4 | MIT | root npm lock |
| fs-minipass | 2.1.0 | ISC | root npm lock |
| fs.realpath | 1.0.0 | ISC | root npm lock |
| fsevents | 2.3.2 | MIT | integration tests |
| fsevents | 2.3.2 | MIT | root npm lock |
| fullcalendar | 6.1.15 | MIT | root npm lock |
| gauge | 3.0.2 | ISC | root npm lock |
| glob | 7.2.3 | ISC | root npm lock |
| has-unicode | 2.0.1 | ISC | root npm lock |
| https-proxy-agent | 5.0.1 | MIT | root npm lock |
| inflight | 1.0.6 | ISC | root npm lock |
| inherits | 2.0.4 | ISC | root npm lock |
| is-fullwidth-code-point | 3.0.0 | MIT | root npm lock |
| javascript-natural-sort | 0.7.1 | MIT | root npm lock |
| jsxgraph | 1.12.2 | (MIT OR LGPL-3.0-or-later) | root npm lock |
| make-dir | 3.1.0 | MIT | root npm lock |
| mathjax | 3.2.2 | Apache-2.0 | root npm lock |
| mathjs | 12.4.2 | Apache-2.0 | root npm lock |
| mdn-data | 2.0.14 | CC0-1.0 | root npm lock |
| mimic-response | 2.1.0 | MIT | root npm lock |
| minimatch | 3.1.5 | ISC | root npm lock |
| minipass | 3.3.6 | ISC | root npm lock |
| minipass | 5.0.0 | ISC | root npm lock |
| minizlib | 2.1.2 | MIT | root npm lock |
| mkdirp | 1.0.4 | MIT | root npm lock |
| ms | 2.1.3 | MIT | root npm lock |
| nan | 2.28.0 | MIT | root npm lock |
| nerdamer | 1.1.13 | MIT | root npm lock |
| next-tick | 1.1.0 | ISC | root npm lock |
| node-fetch | 2.7.0 | MIT | root npm lock |
| nopt | 5.0.0 | ISC | root npm lock |
| npmlog | 5.0.1 | ISC | root npm lock |
| object-assign | 4.1.1 | MIT | root npm lock |
| once | 1.4.0 | ISC | root npm lock |
| pagedjs | 0.4.3 | MIT | root npm lock |
| path-is-absolute | 1.0.1 | MIT | root npm lock |
| playwright | 1.61.0 | Apache-2.0 | integration tests |
| playwright | 1.61.0 | Apache-2.0 | root npm lock |
| playwright-core | 1.61.0 | Apache-2.0 | integration tests |
| playwright-core | 1.61.0 | Apache-2.0 | root npm lock |
| preact | 10.12.1 | MIT | root npm lock |
| prismjs | 1.30.0 | MIT | root npm lock |
| readable-stream | 3.6.2 | MIT | root npm lock |
| regenerator-runtime | 0.13.11 | MIT | root npm lock |
| rimraf | 3.0.2 | ISC | root npm lock |
| safe-buffer | 5.2.1 | MIT | root npm lock |
| seedrandom | 3.0.5 | MIT | root npm lock |
| semver | 6.3.1 | ISC | root npm lock |
| semver | 7.8.5 | ISC | root npm lock |
| set-blocking | 2.0.0 | ISC | root npm lock |
| signal-exit | 3.0.7 | ISC | root npm lock |
| simple-concat | 1.0.1 | MIT | root npm lock |
| simple-get | 3.1.1 | MIT | root npm lock |
| source-map | 0.6.1 | BSD-3-Clause | root npm lock |
| spin-wheel | 4.3.2 | MIT | root npm lock |
| sql.js | 1.8.0 | MIT | root npm lock |
| string-width | 4.2.3 | MIT | root npm lock |
| string_decoder | 1.3.0 | MIT | root npm lock |
| strip-ansi | 6.0.1 | MIT | root npm lock |
| tar | 6.2.1 | ISC | root npm lock |
| tiny-emitter | 2.1.0 | MIT | root npm lock |
| tom-select | 2.4.3 | Apache-2.0 | root npm lock |
| tr46 | 0.0.3 | MIT | root npm lock |
| type | 2.7.3 | ISC | root npm lock |
| typed-function | 4.2.2 | MIT | root npm lock |
| util-deprecate | 1.0.2 | MIT | root npm lock |
| webidl-conversions | 3.0.1 | BSD-2-Clause | root npm lock |
| whatwg-url | 5.0.0 | MIT | root npm lock |
| wide-align | 1.1.5 | ISC | root npm lock |
| wrappy | 1.0.2 | ISC | root npm lock |
| yallist | 4.0.0 | ISC | root npm lock |

The root npm installation supplies browser assets and also contains the integration-test workspace. The integration-test lock is shown independently to make the test toolchain auditable.

## Downloaded and locally packaged components

| Component | Version or revision | License | Distribution role |
| --- | --- | --- | --- |
| `dhall-lang` | `fd057db9b3f89de44cdc77d9669e958b04ed416a` | BSD-3-Clause | Vendored Dhall Prelude source; upstream license file is retained |
| `sbdchd/dhall-docker-compose` | `f589e233de3cdfb6a85d3ced7d35982d1cdf706e` | MIT | Vendored Dhall source; upstream license file is retained |
