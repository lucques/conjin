/////////////
// Sidebar //
/////////////

/*
    Definitions / CSS classes:
    - `<body>` classes:
        - sidebar-active: Sidebar is visible
        - reduced-nav (vs. full-nav): Prune some targets out of nav
    - `.nav-tree-item` classes:
        - hide-on-reduced-nav
            - On reduced-nav: Target is pruned
        - show-only-children-on-reduced-nav
            - On reduced-nav: Target is pruned, but its children are not
        - nested (vs. no-page): Target has children
        - unfolded (vs. folded): Children are unfolded
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

        // Prune as follows:
        // For every target present in the breadcrumb:
        //  (i) Label with `show-only-children-on-reduced-nav`
        // (ii) For all siblings: Label with `hide-on-reduced-nav`

        // Select all breadcrumb items
        const breadcrumbItems = document.querySelectorAll('#sidebar-nav-breadcrumb li');

        breadcrumbItems.forEach(breadcrumbItem => {
            const navItemClass = Array.from(breadcrumbItem.classList).find(cls => cls.startsWith('nav-item_'));

            document.querySelectorAll('#sidebar-nav-tree .' + navItemClass).forEach(item => {
                // (i)
                item.parentElement.classList.add('show-only-children-on-reduced-nav');

                // (ii)
                const siblings = Array.from(item.parentElement.parentElement.children).filter(child => child !== item.parentElement && child.tagName === 'LI');
                siblings.forEach(sibling => {
                    sibling.classList.add('hide-on-reduced-nav');
                });
            })
        });

        // Fold the following parts of the nav:
        // - Above current target: Siblings of parents, grand-parents, etc.
        // - Below current target: Children and below
        // This is checked by the prefix relation of the target ids.
        document.querySelectorAll('#sidebar-nav-tree .nested > .nav-tree-item').forEach(item => {
            const linkIdsString = item.dataset.ids;
            const linkIds       = linkIdsString == '' ? [] : linkIdsString.split(' ');
            if (arrayIsPrefixOf(linkIds, pageIds)) {
                item.parentElement.classList.add('unfolded');
            }
        });
    }

    // Register tree view
    document.querySelectorAll('#sidebar-nav-tree .caret').forEach(item => {
        item.addEventListener('click', event => {
            item.parentElement.classList.toggle('unfolded');
        });
    });

    document.querySelectorAll('#sidebar-nav-tree .no-page').forEach(item => {
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