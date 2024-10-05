# template-bootstrapped

This template can be used in two ways:
1. As a minimalistic template out-of-the-box
2. As the base of a more complex template

What does it provide?
- Extend the Bootstrap framework by some additional features
    - Stacks, slices, ...
    - Further colors, ...
    - ...
- Provide PHP macros to use Bootstrap components

All these extensions are independent of page layout / design etc., i.e. they only provide styles for standalone markup without providing or requiring any context.


## Supported modules
- `math`
    - How? CSS styles
- `mathjax-extensions`
    - How? CSS styles
- `references`
    - How? CSS styles
- `sync-dims`
    - How? Whenever accordions change the page layout, syncing is triggered
- `timetable`
    - How? CSS styles
- `doc-extensions`


## Structure
- All styles are based on Bootstrap which determines the structure of the main files
- There are two main SCSS files, catered to the corresponding media types:
    - `screen.scss`: Setup option B (see below)
    - `print.scss`: Setup option A (see below)
- There are shared SCSS files
    - `partials/_common.scss`: Common styles for both `screen` and `print`
    - `partials/_acc_*.scss` files: Styling for the accordion component
    - `partials/_module_*.scss`: Module-specific styles
- There are SCSS files to modify the Bootstrap framework
    - `constants/_maps_screen.scss`: To be included when setting up the maps
    - `constants/_vars_screen.scss`: To be included when setting up the vars
    - `constants/_vars_print.scss`: To be included when setting up the vars
- Bootstrap setup:
    - Option A (single-line):
        - `@import "bootstrap_scss/bootstrap";`
    - Option A (manual + fine-grained):
        - Since we change some colors, we have to manually set up Bootstrap.
        - See also https://getbootstrap.com/docs/5.3/customize/sass/#maps-and-loops.
- See also the conventions for templates in the conjin docs


## Stacks + Slices
For content flowing top-down, the following notions of a **stack** consisting of **slices** is used throughout.

1. **Stack**: A container with the `.stack` class denotes a container with multiple slices.
2. **Slice**: A slice has `margin-top` and `margin-bottom`, how much varies.

When slices are stacked upon each other, this works very fine as margins are merged, except for the first and last element of the stack. Any `.stack` container eliminates the `margin-top` of the first and `margin-bottom` of the last slice. Sometimes the *visually* first / last element is not the *structurally* (DOM-wise) first / last element. In that case, manually use `first-child` or `last-child` classes. Some containers are made a `.stack` class by default, others can be explicitly made a stack by assigning the `.stack` class

- Stacks:
    - `<section>`, `.card-body`, `.list-group-item`, `<td>`, `.col`, ...
        - See [scss/partials/_common.scss](scss/partials/_common.scss) for the full list
    - The direct children of `.d-grid` and `.d-flex` containers
        - Use `display:grid` and `display:flex` if you don't need the stack behavior.
    - Any element can be made a stack by using the `.stack` class

- Slices:
    - Variable margin: `<h1>`, `<h2>`, ...
    - Medium margin: `<p>`, `<ul>`, `<ol>`, `<pre>`, `<figure>`, `.table`, ...
    - Large margin: `<section>`
    - Any element can be made a slice by using one of these classes:
        - `.p`: Behaves like `<p>`
        - `.pp`: Behaves like 1.5 times `<p>`
        - `.ppp`: Behaves like 2 times `<p>`
        - ...
        - `.section`: Behaves like `<section>`

- Anti-slices:
    - Any element can be made an anti-slice by using the `.my-0` class


## Boxes
The concept of a **box** describes a container with borders, such as `.accordion-body` or `.list-group-item`. Borders are ignored here, only the layouting is important: Padded in y-direction, and slightly more padded in x-direction. There are two types, each with their own class.
- `.box-large`:  Large  padding (default for: `main`)
- `.box-medium`: Medium padding (default for: `.accordion-body`)
- `.box-small`:  Small  padding (default for: `.list-group-item`)


## Spacing: `m-3` etc. 
- Use the standard spacing utilities of Bootstrap, like `m-3`, `row-gap-3` etc.
- All these margins depend on the `$spacer` constant, which is set individually for screen and print media.


## Grid layout, Flex layout: `d-grid`, `d-flex`
- Activated by assigning `.d-grid`, `.d-flex`
- Further specify:
    - `style="grid-template-columns: 1fr 1fr"` etc.
    - `class="column-gap-3"` etc.
- Each grid/flex item is automatically a `.stack`


## Floating elements: `float-left`, `float-end`
Floating elements are typically used for images. Use `.float-left`, `.float-right` which come with a `margin` to the side and bottom to prevent text from sticking to the image.


## Lists

### Ordered lists: `<ol>` 
Next to the common `list-style-types`, there are the following of the form `decimal-circled`:
- `decimal`, `lower-alpha`, `upper-alpha`, `lower-roman`
- `circled`, `bracket`, `parens`

### Problem with styling list markers
There is a problem with styling list markers using CSS (e.g., it is impossible to position the markers on top instead of on baseline). Read more about using `::marker` vs. `::before`+counters. So whenever you want to use the custom markers (using `::before`+counters), there is a possibility:
- `<ol>`: Just add `.ol-decimal-circled` to the `<ol>` element. There is one CSS class for each list-style-type.
- `<ul>`: Just add `.ul-disc` or `.ul-square` to the `<ul>` element.
Space between the list items is deactivated then. Each `<li>` is a stack. To enable spacing, add the `.list-gap` class to the `<ul>` or `<ol>` element.

### Ordered lists, Bootstrap-style: `.list-group.list-group-numbered`
There are two decorations available:
- `.bold-list-markers`
- `.first-child-p`


## Colors and Variants
To keep things lightweight, we reuse the official theme colors...
- `primary`
- `secondary`
- `success`
- `info`
- `warning`
- `danger`
- `light`
- `dark`

...extended by only a few more (defined in [./_maps_screen.scss](./scss/constants/_maps_screen.scss)), such as `orange`

Treating all these just as colors without any semantic meaning is fine even if unintended by Bootstrap devs. We define though the new concept of a **variant** to take up that role of semantic meaning. Variants map semantic functions to before-discussed colors. Here they are:
- Colors
    - Every color is a variant
- "Mathy" stuff
    - `definition`
    - `example`
    - `theorem`
    - `proof`
    - `remark`
    - `exercise`
    - `solution`
    - `hint`
- Layers
    - This is only relevant for niche topics where content is "layered" (e.g. network stack)
    - `layer_1` (top) to `layer_5` (bottom)
- Other
    - `attention`
    - `important`


## Accordion with Item and Block
- An **accordion** consists of multiple **items** (collapsible) and **blocks** (non-collapsible)
- Item
    - Collapsable
    - Introduced by macros `acc_item_start` and `acc_item_end`
- Block
    - Static (non-collapsable)
    - Introduced by macros `acc_block_start` and `acc_block_end`
- DOM structure (naming is not 100% consistent, but that's because the class names are determined by Bootstrap)
    ```html
    <div id="accordion-0" class="accordion">
        <div id="accordion-0-0" class="accordion-item">
            <span class="accordion-header">
                <button class="accordion-button">The title</button>
            </span>
            <div class="accordion-body">The content</div>
        </div>
        <div id="accordion-0-1" class="accordion-block">
            <span class="block-header">
                <span class="block-title">The title</span>
            </span>
            <div class="accordion-body">The content</div>
        </div>
    </div>
    ```
- For single-item or single-block accordion sets, the following shortcuts are available:
    - `acc_single_item_start`  and ...`_end`
    - `acc_single_block_start` and ...`_end`