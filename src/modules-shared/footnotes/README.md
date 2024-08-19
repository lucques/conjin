# footnotes

This module allows to easily insert footnotes. The footnotes can then be printed e.g. at the end of the page.


## Features
- `footnote_start` and `footnote_end` environment
- For template:
    - `footnotes_any` to check whether footnotes were added
    - `footnotes_print_list` to print


## How to support this module
If a template supports this module, it should:
- Render the footnotes at the end of the page using `footnotes_print_list`