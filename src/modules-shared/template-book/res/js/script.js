//////////////////
// Global setup //
//////////////////

function setupAfterScriptsLoaded() {
    // Sync heights:
    // 1. Initially, after MathJax is loaded (hand over to MathJax setup procedure)
    addMathJaxAfterRenderingHook(syncHeights);
    // 2. After every window resize
    window.addEventListener('resize', syncHeights);
    window.addEventListener('resize', syncWidths);
    // 3. After every collapsible gets shown
    setupCollapsiblesForSyncHeights();
    setupCollapsiblesForSyncWidths();

    // Prism.hooks.add('highlight-all', function(env) { console.log('hi'); syncHeights(); });
}

function setupAfterDOMLoaded() {
    setupSidebar();
    // TODO
    // setupScrollspyHack();

    // Prism.highlightAll({
    //     // other options...
    //     callback: function() {
    //       // Your code here, after PrismJS has highlighted the code
    //       syncHeights();
    //     }
    //   });

    // setTimeout(function () {
    //     // Your code here, after a delay (not recommended)
    //     syncHeights();
    //   }, 1000); // Adjust the delay time as needed
      
}


////////////////
// Page setup //
////////////////

/*
    Definitions:
    - sidebar-active: Sidebar is visible (CSS class of `body`)
    - reduced-nav (vs. full-nav): Prune some targets out of nav (CSS class of `body`)
    - hide-on-reduced-nav: Target is pruned out of nav during minimal-view (CSS class of `.nav-item`)
    - unfolded (vs. folded): Children are unfolded (CSS class of `.nav-item`)
*/

function setupSidebar() {
    // Setup Malihu custom scrollbar plugin
    $("#nav").mCustomScrollbar({
        theme: "dark",
        scrollInertia: 0
    });

    // Setup button to activate/deactive sidebar
    document.querySelector('#sidebar-collapse-button').addEventListener('click', toggleSidebar);

    // Setup button to toggle reduced nav
    if (NAV_REDUCE_TOGGLEABLE_WITH_CTRL) {
        document.querySelector('#nav').addEventListener('click', event => {
            if (event.ctrlKey) {
                toggleReducedNav();
            }
        });
    }

    // Initialize values; defaults: Sidebar active, reduced nav
    const activateSidebarOnStartup = localStorage.getItem('sidebar-active') === 'true'
                                  || (localStorage.getItem('sidebar-active') === null &&
                                      NAV_ACTIVE_SIDEBAR_BY_DEFAULT);
    
    const reduceNavOnStartup = localStorage.getItem('reduced-nav') === 'true'
                            || (localStorage.getItem('reduced-nav') === null &&
                                NAV_REDUCE_BY_DEFAULT);

    activateSidebar(activateSidebarOnStartup);
    reduceNav(reduceNavOnStartup);

    // Setup whether top-level nav item is shown or not
    if (NAV_SHOW_TOP_LEVEL) {
        document.querySelector('body').classList.add('show-top-level-nav');
    }

    const pageIdsString = document.querySelector('body').dataset.targetIds
    const pageIds       = pageIdsString == '' ? [] : pageIdsString.split(' ');

    // Prune sibling level-1 nav items unless we are at the root target
    if (pageIds.length > 0) {
        document.querySelectorAll('#nav .item-level-1').forEach(item => {
            const linkIdsString = item.dataset.ids;
            const linkIds       = linkIdsString == '' ? [] : linkIdsString.split(' ');
            
            if (linkIds[0] !== pageIds[0]) {
                item.classList.add('hide-on-reduced-nav');
            }
        });
    }
        
    // Register tree view
    document.querySelectorAll('#nav .caret').forEach(item => {
        item.addEventListener('click', event => {
            item.parentElement.classList.toggle('unfolded');
        });
    });

    // Fold the following parts of the nav:
    // - Above current target: Siblings of parents, grand-parents, etc.
    // - Below current target: Children and below
    // This is easily checked by the prefix relation of the target ids.
    document.querySelectorAll('#nav .nested').forEach(item => {
        const linkIdsString = item.dataset.ids;
        const linkIds       = linkIdsString == '' ? [] : linkIdsString.split(' ');

        if (arrayIsPrefixOf(linkIds, pageIds)) {
            item.classList.add('unfolded');
        }
    });
}

function setupScrollspyHack() {
    // Turn links to anchors on current page to plain anchors
    // like `index.html#anchor` to `#anchor`
    // so that the Bootstrap scroll spy works 
    $(function () {
        var curPath = window.location.href.split('#')[0];
        $('#sidebar a').each(function () {
            var parts = $(this).prop('href').split('#');
            var linkPath = parts[0];
            var hasAnchor = parts.length > 1;

            if (curPath == linkPath) {
                if (hasAnchor) {
                    // Make sure anchor gets highlighted
                    var linkAnchor = parts[1];
                    $(this).prop('href', '#' + linkAnchor);
                }
                else {
                    // Always highlight target
                    $(this).addClass('active');
                }
            }
        });

        var dataSpyList = [].slice.call(document.querySelectorAll('[data-bs-spy="scroll"]'))
        dataSpyList.forEach(function (dataSpyEl) {
        bootstrap.ScrollSpy.getInstance(dataSpyEl)
            .refresh()
        })
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

    // TODO
    // var dataSpyList = [].slice.call(document.querySelectorAll('[data-bs-spy="scroll"]'))
    // dataSpyList.forEach(function (dataSpyEl) {
    // bootstrap.ScrollSpy.getInstance(dataSpyEl)
    //     .refresh()
    // })
}

function toggleReducedNav() {
    reduceNav(!isNavReduced());
}



// TODO: refactor the following (combine into one function etc.)


/////////////////////////
// Synchronize heights //
/////////////////////////

/*
 * The following function synchronizes the heights of all elements that contain
 * the attribute `data-sync-height-id` with the same value.
 */

function syncHeights() {
    const elements = document.querySelectorAll('[data-sync-height-id]');

    // Partition elements by id and reset their height 
    const ids2elements = {};

    elements.forEach(e => {
        // Reset height
        e.style.height = '';

        const id = e.dataset.syncHeightId;
        if (id !== undefined) {
            if (!ids2elements.hasOwnProperty(id)) {
                ids2elements[id] = new Set([e]);
            }
            else {
                ids2elements[id].add(e);
            }
        }
    });

    // For each id, sync heights
    Object.keys(ids2elements).forEach(id => {
        const elements = ids2elements[id];

        let maxHeight = -1;

        // Calculate max height
        for (const e of elements) {
            maxHeight = Math.max(maxHeight, e.clientHeight);
        }

        // Apply max height
        elements.forEach(e => {
            e.style.height = `${maxHeight}px`;
        });
    });
}

function setupCollapsiblesForSyncHeights() {
    const collapsibles = document.querySelectorAll('.collapse');
 
    collapsibles.forEach(e => {
        e.addEventListener('shown.bs.collapse', syncHeights);
    });

}


/////////////////////////
// Synchronize widths //
/////////////////////////

/*
 * The following function synchronizes the widths of all elements that contain
 * the attribute `data-sync-width-id` with the same value.
 */

function syncWidths() {
    const elements = document.querySelectorAll('[data-sync-width-id]');

    // Partition elements by id and reset their width
    const ids2elements = {};

    elements.forEach(e => {
        // Reset width
        e.style.width = '';

        const id = e.dataset.syncWidthId;
        if (id !== undefined) {
            if (!ids2elements.hasOwnProperty(id)) {
                ids2elements[id] = new Set([e]);
            }
            else {
                ids2elements[id].add(e);
            }
        }
    });

    // For each id, sync widths
    Object.keys(ids2elements).forEach(id => {
        const elements = ids2elements[id];

        let maxWidth = -1;

        // Calculate max width
        for (const e of elements) {
            maxWidth = Math.max(maxWidth, e.clientWidth);
        }

        // Apply max width
        elements.forEach(e => {
            e.style.width = `${maxWidth}px`;
        });
    });
}

function setupCollapsiblesForSyncWidths() {
    const collapsibles = document.querySelectorAll('.collapse');
 
    collapsibles.forEach(e => {
        e.addEventListener('shown.bs.collapse', syncWidths);
    });

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