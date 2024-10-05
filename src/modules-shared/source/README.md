# source

This module offers to include source code and to format it nicely. The formatting is done by the Prism library. All output gets escaped by this module.


## Features
- Code listings: Literal
    - `source_start`, `source_end` (environment)
    - `source_listing` (inline)
- Code listings: File
    - `source_file`
    - `source_file_block`: Extract a code block from a file
- Inline code
    - `source_inline`
- Scopes
    - Used to define the language and line numbering of the code
    - `source_scope_start`, `source_scope_end`

## Languages
- `none` (default)
- `java`
- `css`

## Problems
- Line number highlighting does not properly work (rendering happens too late)