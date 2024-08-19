/////////////
// Sidebar //
/////////////

/*
    Definitions:
    - sidebar-active: Sidebar is visible (CSS class of `body`)
    - reduced-nav (vs. full-nav): Prune some targets out of nav (CSS class of `body`)
    - hide-on-reduced-nav: Target is pruned out of nav during minimal-view (CSS class of `.nav-item`)
    - unfolded (vs. folded): Children are unfolded (CSS class of `.nav-item`)
*/

// Non-idempotent; therefore must only be called once
function setupSidebar() {
    // Setup button to activate/deactive sidebar
    document.querySelector('#sidebar-collapse-button-activate').addEventListener('click', toggleSidebar);
    document.querySelector('#sidebar-collapse-button-deactivate').addEventListener('click', toggleSidebar);

    // Functionality to activate/deactivate reduced nav happened already in `header.php`

    // Register initialized values (init has happened in `header.php`): Sidebar active, reduced nav
    activateSidebar(document.querySelector('body').classList.contains('sidebar-active'));
    reduceNav(document.querySelector('body').classList.contains('reduced-nav'));


    /////////////////////////
    // Setup the nav items //
    /////////////////////////

    // If there is a current target (`data-target-ids` body attribute is given)
    if ('targetIds' in document.querySelector('body').dataset) {
        const pageIdsString = document.querySelector('body').dataset.targetIds;
        const pageIds       = pageIdsString == '' ? [] : pageIdsString.split(' ');

        // Prune sibling level-1 nav items if all these hold:
        // 1. We are not at the root target
        // 2. nav_show_top_level is set
        // 3. The given level-1 id exists
        if (pageIds.length > 0 && 'show-top-level-nav' in document.querySelector('body').classList) {
            let found = false;
            document.querySelectorAll('#nav .item-level-1').forEach(item => {
                const linkIdsString = item.dataset.ids;
                const linkIds       = linkIdsString == '' ? [] : linkIdsString.split(' ');
                
                if (linkIds[0] !== pageIds[0]) {
                    item.classList.add('hide-on-reduced-nav');
                }
                else {
                    found = true;
                }
            });

            // Undo pruning if the level-1 nav item is not found
            if (!found) {
                document.querySelectorAll('#nav .item-level-1').forEach(item => {
                    item.classList.remove('hide-on-reduced-nav');
                });
            }
        }

        // Fold the following parts of the nav:
        // - Above current target: Siblings of parents, grand-parents, etc.
        // - Below current target: Children and below
        // This is checked by the prefix relation of the target ids.
        document.querySelectorAll('#nav .nested').forEach(item => {
            const linkIdsString = item.dataset.ids;
            const linkIds       = linkIdsString == '' ? [] : linkIdsString.split(' ');

            if (arrayIsPrefixOf(linkIds, pageIds)) {
                item.classList.add('unfolded');
            }
        });
    }

    // Register tree view
    document.querySelectorAll('#nav .caret').forEach(item => {
        item.addEventListener('click', event => {
            item.parentElement.classList.toggle('unfolded');
        });
    });

    document.querySelectorAll('#nav .no-page').forEach(item => {
        item.addEventListener('click', event => {
            item.parentElement.parentElement.classList.toggle('unfolded');
        });
    });
}

function isSidebarActive() {
    return document.querySelector('body').classList.contains('sidebar-active');
}

function isNavReduced() {
    return document.querySelector('body').classList.contains('reduced-nav');
}

function activateSidebar(active) {
    if (active) {
        document.querySelector('body').classList.add('sidebar-active');
        localStorage.setItem('sidebar-active', 'true');     
    }
    else {
        document.querySelector('body').classList.remove('sidebar-active');
        localStorage.setItem('sidebar-active', 'false');             
    }
}

function reduceNav(reduce) {
    if (reduce) {
        document.querySelector('body').classList.add('reduced-nav');
        localStorage.setItem('reduced-nav', 'true');     
    }
    else {
        document.querySelector('body').classList.remove('reduced-nav');
        localStorage.setItem('reduced-nav', 'false');             
    }
}

function toggleSidebar() {
    activateSidebar(!isSidebarActive());
}

function toggleReducedNav() {
    reduceNav(!isNavReduced());
}


////////////////////////
// Bootstrap Tooltips //
////////////////////////

// Idempotent; may be called again to reinitialize
function setupTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}


/////////////
// Helpers //
/////////////

// Unfortunately not in JS standard library
function arrayIsPrefixOf(prefix, array) {
    if (prefix.length > array.length) {
        return false;
    }

    for (var i = 0; i < prefix.length; i++) {
        if (prefix[i] !== array[i]) {
            return false;
        }
    }

    return true;
}