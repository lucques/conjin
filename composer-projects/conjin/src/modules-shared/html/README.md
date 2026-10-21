# html

This module offers some basic HTML generation, non-flavored, non-opinionated.

## API

### `html_h_with_anchor($level, $content, $id = null, $class = '', $style = '')`

Prints a heading with its title on the left and a `#` permalink on the right. If `$id` is omitted or `null`, the core `kebabize()` helper generates it from `$content`. An explicit ID is used unchanged. Optional classes and styles apply to the heading.

## Module relationships

### How to support this module

Style `.html-heading-anchor` to customize the permalink rendered by `html_h_with_anchor()`, including when called through `nav_h_with_anchor()`. Both `bootstrap-extended` and `bootstrap-alternative` style these links in their secondary color.
