# template-interbook

This template provides the style of an "interactive" "book". The template is based on `template-navigable`. Many modules are supported and there are some config options.


## Usage
This template can be used in two ways:
1. As a template out-of-the-box
2. As the base of a more complex template

See also the docs of the `template-navigable` module.


## Supported modules
- By extension all modules supported by `template-navigable`
- `print-mode`
    - When activated, `wrapper=1` is the default and renders the navigable interbook shell with an iframe pointing to `wrapper=0`.
    - `wrapper=0` renders the standalone interbook document and initializes paged.js there.
    - `screen.scss` is applied only to the wrapper and `print-mode.scss` only to the standalone document.
    - The wrapper's print button opens the browser print dialog for the iframe document; it does not toggle print mode.

Without `print-mode`, the normal interbook document includes `screen.scss` for screen media and `print.scss` for print media.


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
