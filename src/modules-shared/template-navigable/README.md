# template-navigable

This template is very basic and centered around few features:
- **Layout**:
    - `#sidebar` on the left with a navigation, therefore depending on the `nav` module
        - Two toggle features are built-in via `dynamic-toggle` module:
            - Open/close sidebar
            - Reduce/expand navigation
    - `#content` pane on the right
- **Support basic modules**: See also `Supported modules` below.

The sidebar's styles are based on `bootstrap`. While the exact bootstrap configuration is flexible, there must be *some* bootstrap variant used.


## Usage
This template can be used in two ways:
1. As a template out-of-the-box
2. As the base of a more complex template

It forms the base of templates like `template-interbook` and `template-exam`.


## Supported modules
- `doc-extensions`
    - How? Various body classes, scripts etc. are properly inserted
- `footnotes`
    - How? Footnotes are rendered at the end of the page
- `print-mode`
    - How?
        - The `.sheets` are properly included in DOM; toggle button is added
        - `sheets` are treated as stacks
    - If activated, a `print-mode-print.scss` file is required
- `sol-mode`
    - How? Toggle button is added
- `title`
    - How? Titles are put together via this module
- `references`
    - How? References are rendered at the end of the page


## Main structure
Without `print-mode` on, the DOM structure is as follows.

```html
<body>
    <script>// Init scripts that are executed *during* DOM build-up</script>
    <div id="sidebar">
        Sidebar goes here
    </div>
    <div id="content">
        <main>
            Content goes here
        </main>
    </div>
</body>
```


## Sheets
- This is the implementation for the `print-mode` module, see also docs there 
- A `<div class="sheet">` container denotes a single sheet/page if "print mode" is on 
- Apart from that, there is no `<div class="sheet">` container

With `print-mode` on, the DOM structure is as follows.

```html
<body>
    <script>// Init scripts that are executed *during* DOM build-up</script>
    <div id="sidebar">
        Sidebar goes here
    </div>
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
</body>
```