# nav-build

This module offers to build up a navigation tree of all the preprocessed content. The tree is built up mostly in the preprocessing phase, but anchors may be added in the processing phase. This is because anchors are not part of meta data that is available during the preprocessing phase.


## General usage

The nav is built up during the preprocessing phase. The navigation is built up recursively, so if at any point in the target tree you deactivate this module, then this is fine and the navigation tree is cut. There is one target with a special role and this is crucial: If this module is activated for the **root content target** then it will save the built-up tree (an object of type `NavItem`) persistently. So it is crucial to activate this module for the root target, since in the end there will be **only one unqiue nav object** printed.


## Usage in *content file*

Add headings that are to appear in the navigation. They get inserted into the hierarchy as follows: The current target has level 1. So the next sub-heading should be of level 2. Such a heading serves as an HTML anchor and the anchor id is either generated (error is thrown on name clashes)...

```html
<? nav_h(2, 'Sub-heading');  // Generated anchor id is "sub_heading"  ?> 
```

... or manually assigned:

```html
<? nav_h(2, 'Sub-heading', 'the-anchor-id');  // Generated anchor id is "the-anchor-id"  ?> 
```