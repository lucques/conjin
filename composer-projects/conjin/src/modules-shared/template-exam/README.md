# template-exam

Template for tests and exams.

Both `sol-mode` and `print-mode` modules are automatically activated.

## Print wrapper
`template-exam` uses the same wrapper mechanism as `template-navigable`. `wrapper=1` is the default and renders the navigable exam shell with an iframe pointing to `wrapper=0`. `wrapper=0` renders the standalone exam document and initializes paged.js there. `screen.scss` is applied only to the wrapper, while the exam-specific `print-mode.scss` is applied only to the standalone document. The wrapper's print button opens the browser print dialog for the iframe document; it does not toggle print mode.


## Supported modules
- By extension, all modules supported by:
    - `template-navigable`
