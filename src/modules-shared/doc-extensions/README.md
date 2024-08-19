# doc-extensions

This module offers to extend the HTML page in three ways.
1. Collect `<link>`, `<style>` etc. for `<head>` section
2. Collect classes for `<body>`
3. Collect JS lines to be included at the end of `<body>`


## How to support this module
Any template that supports this module must retrieve these three elements and insert them at the right places in the generated output.