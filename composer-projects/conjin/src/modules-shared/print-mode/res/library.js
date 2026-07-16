function setupPrintMode(cssPath) {
    new Paged.Previewer().preview(undefined, [cssPath]).then(() => {
        document.head.append(document.querySelector('#print-mode-interface-stylesheet'));
        window.printModeReady = true;
        window.dispatchEvent(new Event('print-mode-ready'));
    });
}

function setupPrintModeAfterMathJax(cssPath) {
    const setup = () => setupPrintMode(cssPath);

    if (typeof window.MathJax?.startup?.promise !== 'undefined') {
        window.MathJax.startup.promise.then(setup);
    }
    else if (typeof window.mathJaxAfterRenderingHooks !== 'undefined') {
        window.mathJaxAfterRenderingHooks.push(setup);
    }
    else {
        window.mathJaxAfterRenderingHooks = [setup];
    }
}
