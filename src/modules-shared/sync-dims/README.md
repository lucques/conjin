# sync-dims

Problem description: Sometimes CSS does not suffice to sync dimensions, i.e. widths or heights, of certain elements. Mostly, this is because the elements are structurally separated, e.g. in different containers, and CSS does not provide a way to sync them.

This module offers a solution to this problem.

Just attach a...
- `data-sync-width-id="SOME_ID"` or
- `data-sync-height-id="SOME_ID"` attribute
to a DOM element and then those with the same given ID will be kept in sync.

The sync mechanism is setup up initially and then syncing can be manually triggered by calling `syncWidths()` and `syncHeights()`. A module that supports this module will take care of re-syncing, e.g. the `template-bootstrapped` template will make sure accordions are resynced every time collapses changes the layout.