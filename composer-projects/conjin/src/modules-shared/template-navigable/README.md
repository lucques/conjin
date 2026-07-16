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
- `localization`
    - How? Language in meta data + Language switcher is added to the sidebar
- `print-mode`
    - How?
        - The wrapper document is rendered by default.
        - The standalone print document is rendered separately and processed by paged.js.
    - If activated, `screen.scss` styles the wrapper and `print-mode.scss` styles the standalone document.

Without `print-mode`, the normal document includes both `screen.scss` for screen media and `print.scss` for print media. The print-mode wrapper includes only `screen.scss`; its standalone iframe document includes `print-mode.scss` for all media.
- `sol-mode`
    - How? Toggle button is added
- `title`
    - How? Titles are put together via this module
- `references`
    - How? References are rendered at the end of the page


## Main structure
Without the `print-mode` module, the DOM structure is as follows.

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


## Print wrapper
When `print-mode` is activated, `template-navigable` implements an internal wrapper mechanism using `?wrapper=0` and `?wrapper=1`.

`wrapper=1` is the default and renders the normal navigable shell with an iframe in place of the `<main>` content. The iframe points to the same target with `wrapper=0`. `wrapper=0` renders only a minimal standalone document and initializes paged.js there. This isolates paged.js and the generated pages from the navigation document. The wrapper's print button opens the browser print dialog for the iframe document; it does not toggle print mode.

The wrapper document has this structure:

```html
<body>
    <div id="sidebar">
        Sidebar goes here
    </div>
    <div id="content">
        <main>
            <iframe id="print-mode-iframe" src="./?wrapper=0"></iframe>
        </main>
    </div>
</body>
```

Only the iframe document contains the `.sheet` elements used by paged.js.
