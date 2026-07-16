# doc-extensions

Collects trusted document fragments during processing for a compatible template to render.

## Usage

Activate it with `$c->activate_module('doc-extensions')`.

## API

| Extension | Add directly | Output-buffer shortcut |
| --- | --- | --- |
| `<head>` element | `doc_extensions_add_head_element()` or `doc_extensions_prepend_head_element()` | `head_start()` / `head_end()` |
| Inline CSS in `<head>` | — | `css_start()` / `css_end()` |
| `<body>` class | `doc_extensions_add_body_class()` | — |
| Top of `<body>` | `doc_extensions_add_body_top_element()` | `body_top_element_start()` / `body_top_element_end()` |
| Bottom of `<body>` | `doc_extensions_add_body_bottom_element()` | `body_bottom_element_start()` / `body_bottom_element_end()` |
| JavaScript after DOM setup | `doc_extensions_add_js_after_dom_setup()` | `js_after_dom_setup_start()` / `js_after_dom_setup_end()` |

Insertion order is preserved; `doc_extensions_prepend_head_element()` inserts before existing head extensions. Values are rendered verbatim.

## Module relationships

### How to support this module

A supporting template must place head elements in `<head>`, classes on `<body>`, body-top and body-bottom elements inside `<body>`, and DOM-setup JavaScript after page content and template setup.
