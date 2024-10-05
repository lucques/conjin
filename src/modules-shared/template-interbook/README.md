# template-interbook

This template provides the style of an "interactive" "book". The template is based on `template-bootstrapped`. Many modules are supported and there are some config options.

This template can be used in two ways:
1. As a template out-of-the-box
2. As the base of a more complex template

See also the docs of the `template-bootstrapped` module.


## Supported modules
- By extension all modules supported by `template-bootstrapped`
- By extension all modules supported by `template-navigable`


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