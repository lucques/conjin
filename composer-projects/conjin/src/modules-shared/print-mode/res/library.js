function setupPrintMode(cssPath, renderer = 'pagedjs') {
    if (window.printModePromise) {
        return window.printModePromise;
    }

    window.printModeReady = false;
    window.printModePromise = (async () => {
        try {
            await pagePrep.wait('readyForPagination');
            if (renderer === 'pagedjs') {
                await new Paged.Previewer().preview(undefined, [cssPath]);
                document.head.append(document.querySelector('#print-mode-interface-stylesheet'));
            }
            else if (renderer === 'native') {
                const sheets = document.querySelectorAll('.sheet');
                if (sheets.length > 0) {
                    // Firefox otherwise adds an unnamed page after the final named sheet.
                    document.body.style.page = getComputedStyle(sheets[sheets.length - 1]).page;
                }
            }
            else {
                throw new Error(`Unknown print-mode renderer: ${renderer}`);
            }
            window.printModeReady = true;
            window.dispatchEvent(new Event('print-mode-ready'));
        }
        catch (error) {
            window.printModeError = error;
            const message = document.createElement('p');
            message.id = 'print-mode-error';
            message.setAttribute('role', 'alert');
            message.textContent = `Print preparation failed: ${error.message}`;
            document.body.prepend(message);
            console.error('Print preparation failed:', error);
            window.dispatchEvent(new CustomEvent('print-mode-error', { detail: error }));
            throw error;
        }
    })();

    window.printModePromise.catch(() => {});
    return window.printModePromise;
}
