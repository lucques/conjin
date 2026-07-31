# Third-party notices

Conjin-authored code is licensed under `GPL-3.0-or-later`. The components below are not relicensed by Conjin: each remains subject to its stated license. The `./build-assets.sh` command collects package metadata and all top-level legal files supplied by locked npm packages into `third-party-licenses/npm/`, which is included in the assembled Composer package. License files contained in downloaded archives remain alongside those assets.

## Redistributed browser assets

| Module | Component and pinned version | License |
| --- | --- | --- |
| `bootstrap-icons` | Bootstrap Icons 1.11.3 | MIT |
| `bootstrap` | Bootstrap 5.3.3 and Popper 2.11.8 | MIT |
| `chart-js` | Chart.js 4.5.1 | MIT |
| `fullcalendar` | FullCalendar 6.1.15, FullCalendar Bootstrap 5 6.1.15, and FullCalendar DayGrid 6.1.15 | MIT |
| `jsxgraph` | JSXGraph 1.12.2 | MIT OR LGPL-3.0-or-later; Conjin redistributes it under the MIT option |
| `mathjax` | MathJax 3.2.2 | Apache-2.0 |
| `mathjs` | math.js 12.4.2 | Apache-2.0; upstream `NOTICE` retained |
| `nerdamer` | Nerdamer 1.1.13 | MIT |
| `paged-js` | paged.js 0.4.3 | MIT |
| `picocss` | Pico CSS 2.0.6 | MIT |
| `prism` | PrismJS 1.30.0 | MIT |
| `spinning-wheel` | spin-wheel 4.3.2 | MIT |
| `sql-js` | sql.js 1.8.0 | MIT |
| `tom-select` | Tom Select 2.4.3 | Apache-2.0 |

## Vendored Dhall sources

The generated Composer package contains the pinned `dhall-lang` Prelude at revision `fd057db9b3f89de44cdc77d9669e958b04ed416a`; its BSD-3-Clause `LICENSE` is retained in the vendored directory.

The generated package also contains `sbdchd/dhall-docker-compose` at revision `f589e233de3cdfb6a85d3ced7d35982d1cdf706e`, licensed under MIT upstream. Its upstream `LICENSE` is retained alongside the pinned source.

## Composer dependencies

Composer dependencies are not bundled with this package. Composer resolves them from `composer.json` and installs each dependency as a separate package, preserving its license files. The consuming project's `composer.lock` records the exact versions selected for that installation.

## Release status

No unresolved licensing blocker is currently identified for the assembled Composer package by the dependency inventory and retained legal files. This notice is an inventory and compliance aid, not legal advice.
