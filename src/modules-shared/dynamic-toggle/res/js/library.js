function dtSetup(cssClass) {
    // Precondition: CSS class has already initially been attached to <body>. 
    // Now: Activate/deactivate according to it.
    dtActivate(cssClass, document.querySelector('body').classList.contains(cssClass));
}

function dtIsOn(cssClass) {
    return document.querySelector('body').classList.contains(cssClass);
}

function dtActivate(cssClass, modeOn) {
    if (modeOn) {
        document.querySelector('body').classList.add(cssClass);
        localStorage.setItem('dynamic-toggle-' + cssClass, 'true');
    }
    else {
        document.querySelector('body').classList.remove(cssClass);
        localStorage.setItem('dynamic-toggle-' + cssClass, 'false');
    }
}

function dtToggle(cssClass) {
    dtActivate(cssClass, !dtIsOn(cssClass));
}