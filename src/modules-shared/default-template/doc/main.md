# Documentation of `default-template`


## Overview
- All styles are based on Bootstrap which determines the structure of the main files
- There are two main files, catered to the corresponding media types:
    - `_screen.scss`: Custom Bootstrap setup, since colors etc. are added
    - `_print.scss`: Simple Bootstrap setup (one-liner) 
- Everything is imported from these two main files, transitively as follows.
    ```
    - _screen.scss
        - partials/_common.scss
        - partials/_module_accordion_screen.scss
        - ...
    - _print.scss
        - partials/_common.scss
        - partials/_module_accordion_print.scss
        - ...
    - ./partials/_common.scss
        - partials/_module_accordion_common.scss
        - ...
    ```
- Activating "print mode" (by user via button) is not the same as enabling `print` media (by browser)
- "print mode" is ignored here since it is a module of itself and not part of this template. The only thing that matters here is that the `<div class="sheet">`-wrappers are properly ignored.


## Page and Layout
- Standard DOM
    ```
    <div id="content">
        <main>
            Content goes here
        </main>
    </div>
    ```
- **Sheet**
    - A `<div class="sheet">` container denotes a single sheet/page if "print mode" is activated 
    - Else, there is no `<div class="sheet">` container
    - DOM when "print mode" is activated
        ```
        <div id="content">
            <main>
                <div class="sheet A5 landscape">
                    Content on first page
                </div>
                <div class="sheet">
                    Content on second page
                </div>
            </main>
        </div>
        ```
- **Stack**
    - A container with the `.stack` class denotes a container with multiple content elements (e.g. `h1`, `p`, `ul`).
    - Typically, the content elements have `margin-top` and `margin-bottom`
    - When these children are stacked upon each other, this works very fine, except for the first and last element of the stack
    - By being in a `.stack` container though, the `margin-top` of the first and `margin-bottom` of the last element are eliminated 
    - Some containers are made a `.stack` class by default (e.g. `main`, `td`), others can be explicitly made a stack by assigning the `.stack` class

## Content

### Semantic functions
- **Variant**: Content of certain semantic function
    - definition
    - example
    - exercise
    - solution
- **Layer**: This is only relevant for niche topics where content is "layered" (e.g. network stack)
    - layer_1 (top) to layer_5 (bottom)


### DOM elements
A **Content DOM element** is e.g. `h1`, `p`, `ul`.

- **Accordion** with **Item** and **Block**
    - An accordion set consists of multiple collapsable items and blocks
    - Item
        - Collapsable
    - Block
        - Static (non-collapsable)
        - DOM structure (naming is not 100% consistent, but that's because the class names are determined by Bootstrap)
            ```
            <div class="accordion">
                <div class="accordion-item">
                    <span class="accordion-header">
                        <button class="accordion-button">The title</button>
                    </span>
                    <div class="accordion-body">The content</div>
                </div>
                <div class="accordion-block">
                    <span class="block-header">
                        <span class="block-title">The title</span>
                    </span>
                    <div class="accordion-body">The content</div>
                </div>
            </div>
            ```
- **Headings** in box
    - A box is something like accordion-items, accordion-block, card-body etc.
    - The headinge `h1` to `h6` can be still used in accordance to the rest of the document.
    - For styling though, assign the classes `boxed-h1` to `boxed-h6`.
    - By default, those heading are not font-weighted
    - Example:
        ```
        <h3 class="boxed-h1">Title-title</h3>
        ```
- **Section**: `<section>`

### Margins
Content elememts typically have `margin-top` and `margin-bottom`. To bring some structure into margins, the following variables are defined:
- `$margin_p`
    - Margin around a `p`, `ul`, `ol`, `pre`, `figure`, `.table`
- `$margin_float`
    - Margin around something like `img` floating around text
- `$margin_section`
    - Margin around a `section`




## `_screen.scss`

