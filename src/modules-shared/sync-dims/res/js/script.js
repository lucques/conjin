//////////////////
// Global state //
//////////////////

let syncWidthsElements = null;
let syncWidthIds2elements = null;

let syncHeightsElements = null;
let syncHeightIds2elements = null;


///////////
// Setup //
///////////

// Idempotent; may therefore be recalled when the DOM changed
function setupSyncWidths() {
    syncWidthsElements = document.querySelectorAll('[data-sync-width-id]');
    syncWidthIds2elements = {};

    // Partition elements by id
    syncWidthsElements.forEach(e => {
        const id = e.dataset.syncWidthId;
        if (id !== undefined) {
            if (!syncWidthIds2elements.hasOwnProperty(id)) {
                syncWidthIds2elements[id] = new Set([e]);
            }
            else {
                syncWidthIds2elements[id].add(e);
            }
        }
    });
}

// Idempotent; may therefore be recalled when the DOM changed
function setupSyncHeights() {
    syncHeightsElements = document.querySelectorAll('[data-sync-height-id]');
    syncHeightIds2elements = {};

    // Partition elements by id
    syncHeightsElements.forEach(e => {
        const id = e.dataset.syncHeightId;
        if (id !== undefined) {
            if (!syncHeightIds2elements.hasOwnProperty(id)) {
                syncHeightIds2elements[id] = new Set([e]);
            }
            else {
                syncHeightIds2elements[id].add(e);
            }
        }
    });
}


////////////////////
// Sync functions //
////////////////////

function syncWidths() {
    // Reset all widths
    syncWidthsElements.forEach(e => {
        e.style.width = '';
    });

    // For each id, sync widths
    Object.keys(syncWidthIds2elements).forEach(id => {
        const elements = syncWidthIds2elements[id];

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

function syncHeights() {
    // Reset all heights
    syncHeightsElements.forEach(e => {
        e.style.height = '';
    });

    // For each id, sync heights
    Object.keys(syncHeightIds2elements).forEach(id => {
        const elements = syncHeightIds2elements[id];

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