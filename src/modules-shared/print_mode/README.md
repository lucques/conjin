# print_mode

This module offers a button on the page that activates the so-called "print mode". This is a rendering mode catered specifically for printing out the web page, typically the page-to-be-printed will be shown on the browser screen already.

The mode must be explicitly *enabled*, activating this module does not suffice.

## Terminology
- Mode *enabled*: Offers the button
- Mode *requested*: User requests mode via query string in URL (e.g. clicked on the button)
- Mode *active*: Enabled and requested, or "alway-active" flag set

## Features
- Option to enable/disable mode (default: disabled)
- Option for size (default: A4)
- Option for orientation (default: portrait)
- Functions:
    - `css_print_mode_start` and `css_print_mode_end`:
        - Work like `css_start` and `css_end` but only when print mode is activated
    - `page_start` and `page_end` and `page_break` functions
        - Used to separate pages, allow orientation change on-the-fly.
    - ...

## Design and Layout
- Main styles must be provided by the template (both screen and print media)
- The rendering as pages is provided by the `paper-css` module, which is based on markup containing so-called "sheets". The "sheet" containers are only added when print mode is active. 
    - DOM when "print mode" is activated
        ```
        <div class="sheet A5 landscape">
            Content on first page
        </div>
        <div class="sheet">
            Content on second page
        </div>
        ```