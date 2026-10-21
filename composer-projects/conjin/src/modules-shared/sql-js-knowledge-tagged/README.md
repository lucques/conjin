# sql-js-knowledge-tagged

Macros for setting up and using tag-based knowledge databases, based on sql-js.

There is a specific macro for adding gallery items called (`sql_js_add_gallery_item`).

When page-prep is active, initial tag controls and item insertion are registered together before initialContentReady. Otherwise they run asynchronously without readiness tracking. Later filtering and history updates remain interactive work.
