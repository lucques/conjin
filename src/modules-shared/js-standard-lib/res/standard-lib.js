////////////////
// Assertions //
////////////////

function assert(condition, message = 'No message') {
    if (!condition) {
        console.error('Assertion failed:', message);
    }
}


////////////////
// Unique IDs //
////////////////

let uniqueIdCounter = 0;
function nextUniqueId() {
    return 'id-' + uniqueIdCounter++;
}


////////////////////
// URL parameters //
////////////////////

function getURLParameter(param) {
    const url = new URL(window.location.href);
    return url.searchParams.get(param); // Returns the value of the parameter or null
}

// Update without reloading; add to browser history
function updateURLParameter(param, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(param, value); // Add or update the parameter
    } else {
        url.searchParams.delete(param); // Remove the parameter if value is null
    }
    window.history.pushState({}, '', url); // Update the URL without reloading
}