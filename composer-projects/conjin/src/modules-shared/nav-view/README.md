# nav-view

This module offers to print the navigation tree that was built up by the `nav-build` module.


## Usage in *template*

Print the nav as follows. If the current user is not privileged to view a target, then the target and its children are omitted.

```php
<? nav_print($breadcrumb_up_to_level, with_decoration: true); ?>
```
