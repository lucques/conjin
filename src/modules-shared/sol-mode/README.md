# sol-mode

This module offers a button on the page that activates the so-called **solution mode**. If the button is not clicked, the page remains in "exercise mode". The whole target is viewed as an exercise sheet and when the solution mode is activated, the same page including the solutions are shown.

Activating this module for a target will add the toggle button to the page if the employed template supports this module.


## Features
Only the essentials. All other things like marking the solution in red etc. must be provided by extra-defined macros
- Provide the mechanism for switching between exercise and solution mode
- Authorization
- `sol_start`, `sol_end` environment
- `sol_start`, `sol_gap`, `sol_end` environment (two-staged environment)


## Terminology:
- Mode *requested*: User requests mode via query string in URL (e.g. clicked on the button)
- Mode *on*:        Either *requested* or always on (via config)


## How to support this module
If a template supports this module, it should:
- Render some sort of toggle-button


## Authorization
By default, everyone can view the solution
- Option `restrict_to_groups`: Restrict access to groups
- Option `restrict_to_users`: Restrict access to users
- If both options are used, then the restrictions are "OR"ed


## `sol-mode` vs. `exercise` modules
Since both modules offer a different mechanism to reveal solutions, only one of the modules should be used.
1. Use `sol-mode` if the page itself is an exercise, and the whole page solution may be revealed
2. Use `exercise` if there are multiple exercises, each with individual solutions