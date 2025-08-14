# exercise

This module offers to include exercises and solutions. It depends on the accordions of `bootstrap-extended`.


## Features
Only the essentials. All other things like highlighting the solution in red etc. must be provided by extra-defined macros
- Authorization: Restrict solutions to authorized actors
- Multi-staged environment: `ex_start`, `ex_item`, `ex_hint`, `ex_sol`


## `sol-mode` vs. `exercise` modules
Since both modules offer a different mechanism to reveal solutions, only one of the modules should be used.
1. Use `sol-mode` if the page itself is an exercise, and the whole page solution may be revealed
2. Use `exercise` if there are multiple exercises, each with individual solutions