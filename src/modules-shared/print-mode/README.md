# print-mode

This module offers a button on the page that activates the so-called "print mode". This is a rendering mode catered specifically for printing out the web page, typically the page-to-be-printed will be shown on the browser screen already.

Activating this module for a target will add the toggle button to the page if the employed template supports this module.


## Features
- Options (see "config")
- Uses `paged-js` for nice print rendering
- Functions:
    - Custom page flow:
        - `page_start`, `page_end`, `page_break` functions
    - `css_print_mode_start` and `css_print_mode_end`:
        - Work like `css_start` and `css_end` but only when print mode is on


## Terminology
- Mode *requested*: User requests mode via query string in URL (e.g. clicked on the button)
- Mode *on*:        Either *requested* or always on (via config)


## Supported modules
- `mathjax-extensions`
    - How? When `mathjax-extensions` and `print-mode` are both active, the order of setting up the two modules is important:
        1. Render mathjax, which may change the layout
        2. Render the print mode.


## How to support this module
If a template supports this module, it should:
- Render some sort of toggle-button
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
    - Initial `page_start` automatically inserted
    - Final `page_end` automatically inserted
    - `page_break`s by user


## Design and Layout
- Main styles must be provided by the template (both screen and print media)
- The rendering as pages is provided by the `paged-js` module, which is based on markup containing so-called "sheets". The "sheet" containers are only added when print mode is on. 
    - DOM when "print mode" is on:
        ```html
        <div class="sheet A5-landscape">
            Content on first page
        </div>
        <div class="sheet">
            Content on second page
        </div>
        ```


## Limitations / Bugs
1. Single pages were supposed to be configurable using the `next_size` and `is_next_portrait` flags in `page_break`. I did not get named pages (see also CSS specs) to work, so this feature is not available (the flags exist nevertheless but don't have an effect). Also the CSS classes like `A5-landscape` are attached to the sheets, but they will not have any effect either. This is a future TODO.

2. In some browsers there comes an error up (this is Chrome)
```
TypeError: Cannot read properties of null (reading 'nextSibling')
    at nextSignificantNode (paged.js:840:21)
    at nodeAfter (paged.js:231:25)
    at Layout.findEndToken (paged.js:2091:16)
    at Page.checkUnderflowAfterResize (paged.js:2460:37)
    at paged.js:2432:13
```
I did not manage to get rid of this error, but since the pages are rendered fine I will stick with it for now. It may have something to do with removing the original `#content main` container. It definitely has something to do with manual page breaks. Not clear though how to reproduce the problem, also the same page did not provoke an error in Firefox but in Chrome... weird. Future TODO.