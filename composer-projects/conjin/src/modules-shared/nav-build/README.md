# nav-build

This module offers to build up a navigation tree of all the preprocessed content. The tree is built up mostly in the preprocessing phase, but anchors may be added in the processing phase. This is because anchors are not part of meta data that is available during the preprocessing phase.


## General usage

The nav is built up during the preprocessing phase. The navigation is built up recursively, so if at any point in the target tree you deactivate this module, then this is fine and the navigation tree is cut. There is one target with a special role and this is crucial: If this module is activated for the **root content target** then it will save the built-up tree (an object of type `NavItem`) persistently. So it is crucial to activate this module for the root target, since in the end there will be **only one unqiue nav object** printed.


## Usage in *content file*

Add headings that are to appear in the navigation. They get inserted into the hierarchy as follows: The current target has level 1. So the next sub-heading should be of level 2. Such a heading serves as an HTML anchor and the anchor id is either generated (error is thrown on name clashes)...

```html
<? nav_h(2, 'Sub-heading');  // Generated anchor id is "sub-heading"  ?>
```

... or manually assigned:

```html
<? nav_h(2, 'Sub-heading', id: 'the-anchor-id');  // Anchor id is "the-anchor-id"  ?>
```

Use `nav_h_with_anchor()` to add a right-aligned `#` permalink to the heading, rendered by `html_h_with_anchor()`:

```php
nav_h_with_anchor(2, 'Sub-heading');
nav_h_with_anchor(3, 'Detailed heading', nav_title: 'Short title', id: 'details');
```

Both functions accept `($level, $title, $nav_title = null, $id = null, $class = '', $style = '')` and register headings in the same navigation and anchor hierarchy. Omitted or `null` IDs are generated with `kebabize($title)`. The permalink uses the complete hierarchical anchor ID, such as `#sub-heading_details` in this example. The navigation title defaults to `$title` and does not include the permalink. Optional classes and styles apply to the heading.
