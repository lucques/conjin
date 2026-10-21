# print-mode

This module provides Paged.js or native browser rendering for printable pages.

Print mode is determined exclusively by module activation. If `print-mode` is activated for a target, it is active; otherwise it is inactive. There is no query parameter, runtime toggle, or `always_on` option.


## Renderer configuration

`renderer` selects `pagedjs` (default) or `native`. `renderer_in_sol_mode` overrides it when authorized solution mode is on; its default `null` inherits `renderer`. The override also respects sol-mode's `always_on` and `always_off` settings.

Both renderers use the same wrapper, standalone document, print styles and readiness barrier. Native mode shows continuous content on screen and uses browser pagination when printing, retaining explicit `page_break()` calls. It loads no Paged.js script or preview interface. The outer document retains the final sheet’s named page to prevent a trailing blank page in Firefox. Templates can select defaults when activating print-mode; template-exam uses `pagedjs` for exercises and `native` for solutions.


## Features
- Options (see "config")
- Uses `paged-js` or native browser pagination for print rendering
- Starts pagination after the page-prep readyForPagination barrier resolves.
- Functions:
    - Custom page flow:
        - `page_start`, `page_end`, `page_break` functions
    - CSS just for print mode:
        - `css_print_mode_start` and `css_print_mode_end`
        - Work like `css_start` and `css_end`; supporting templates include the collected CSS only in the standalone print document.


## Module relationships

### Dependencies

- `doc-extensions`: Adds the print scripts and styles to the standalone document.
- `page-prep`: Coordinates source-document readiness; print-mode waits for `readyForPagination`.
- `paged-js`: Paginates the prepared source document when the Paged.js renderer is selected.

Print-mode does not depend on any content producer or renderer. Each active producer registers its own work with page-prep, which determines when pagination can start.

## Browser lifecycle

`setupPrintMode(cssPath, renderer = 'pagedjs')` starts preparation once and returns the same `window.printModePromise` on subsequent calls. Successful print preparation sets `window.printModeReady` and emits `print-mode-ready`.

Modules and pages that produce initial content register their work with page-prep. Print-mode consumes only the final `readyForPagination` barrier. See [document readiness](../../../../../doc/document_readiness.md) for stage and resource contracts.

A preparation failure leaves the source DOM intact, displays `#print-mode-error`, stores the error in `window.printModeError`, emits `print-mode-error` with the error as its detail, and rejects `printModePromise`. It never emits `print-mode-ready`. Console reporting is attached automatically; callers can also await the rejection. Failures inside Paged.js itself may occur after Paged.js has already replaced the source DOM.

See [document readiness](../../../../../doc/document_readiness.md) for the module integration contract.


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
