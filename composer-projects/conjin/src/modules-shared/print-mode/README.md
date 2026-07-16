# print-mode

This module provides paged.js-based rendering for printable pages.

Print mode is determined exclusively by module activation. If `print-mode` is activated for a target, it is active; otherwise it is inactive. There is no query parameter, runtime toggle, or `always_on` option.


## Features
- Options (see "config")
- Uses `paged-js` for nice print rendering
- Functions:
    - Custom page flow:
        - `page_start`, `page_end`, `page_break` functions
    - CSS just for print mode:
        - `css_print_mode_start` and `css_print_mode_end`
        - Work like `css_start` and `css_end`; supporting templates include the collected CSS only in the standalone print document.


## Supported modules
- `mathjax-extensions`
    - How? When `mathjax-extensions` and `print-mode` are both active, the order of setting up the two modules is important:
        1. Render mathjax, which may change the layout
        2. Render the print mode.


## How to support this module
If a template supports this module, it should:
- Render a wrapper document by default and a standalone print document for `?wrapper=0`.
- Call `print_mode_prepare_document()` only for the standalone document.
- Apply `screen.scss` only to the wrapper document and the template-specific `print-mode.scss` only to the standalone document.
- Make sure the DOM is prepared as follows:
    - DOM should have this form:
    ```html
    <div id="content">
        <main>
            <?= page_start(); ?>
            Content
            <?= page_break(); ?>
            Content
            <?= page_end(); ?>
        </main>
    </div>
    ```
    - Initial `page_start` should be automatically inserted
    - Final `page_end` should be automatically inserted
    - `page_break`s should be given by user


## Design and Layout
- Main styles must be provided by the template (both screen and print media)
- The rendering as pages is provided by the `paged-js` module, which is based on markup containing so-called "sheets". The "sheet" containers are only added when print mode is on. 
    - DOM when "print mode" is on:
        ```html
        <div class="sheet a4-landscape">
            Content on first page
        </div>
        <div class="sheet a4-portrait">
            Content on second page
        </div>
        ```


## Page size and orientation
Individual pages can switch between A4 and A5 and between portrait and landscape through the `next_size` and `is_next_portrait` arguments of `page_break`. For example, `page_break('a4', false)` starts an A4 landscape page and `page_break('a5', true)` starts an A5 portrait page.

`res/page.css` contains the shared named-page definitions. The size- and orientation-specific stylesheets import it and define only the document-wide default page.

## Limitations
Chrome may scale the content unexpectedly when printing a document that mixes portrait and landscape pages. The paged.js screen preview and physical PDF page dimensions are correct, but Chrome calculates print shrink-to-fit scaling from the widest generated page.

Mixing A4 and A5 pages does not print reliably in Chrome. A5 support and the demonstration pages are retained, but no additional scaling workaround is applied.
