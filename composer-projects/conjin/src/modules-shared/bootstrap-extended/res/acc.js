(() => {
    let revealController;

    const waitForTransition = (collapse, signal) => new Promise(resolve => {
        if (signal.aborted || !collapse.classList.contains('collapsing')) {
            resolve();
            return;
        }

        const finish = event => {
            // Bootstrap collapse events bubble from nested panels.
            if (event.type !== 'abort' && event.target !== collapse) return;
            collapse.removeEventListener('shown.bs.collapse', finish);
            collapse.removeEventListener('hidden.bs.collapse', finish);
            signal.removeEventListener('abort', finish);
            resolve();
        };
        collapse.addEventListener('shown.bs.collapse', finish);
        collapse.addEventListener('hidden.bs.collapse', finish);
        signal.addEventListener('abort', finish, { once: true });
    });

    const waitForAccordionTransitions = async (accordion, signal) => {
        while (!signal.aborted) {
            const transitioning = [accordion, ...accordion.querySelectorAll('.accordion-collapse')]
                .filter(element => element.classList.contains('collapsing'));
            if (!transitioning.length) return;
            await Promise.all(transitioning.map(element => waitForTransition(element, signal)));
        }
    };

    const revealHashTarget = async () => {
        revealController?.abort();
        revealController = new AbortController();
        const { signal } = revealController;

        let id;
        try {
            id = decodeURIComponent(window.location.hash.slice(1));
        } catch {
            return;
        }
        if (!id) return;

        const target = document.getElementById(id);
        if (!target) return;

        const collapses = [];
        // Item permalinks point to the wrapper so scrolling includes the title.
        if (target.matches('.accordion-item')) {
            const collapse = target.querySelector(':scope > .accordion-collapse');
            if (collapse) collapses.push(collapse);
        }
        for (let element = target; element; element = element.parentElement) {
            if (element.matches('.accordion-collapse')) collapses.unshift(element);
        }
        if (!collapses.length) return;

        for (const collapse of collapses) {
            // A transitioning sibling in an exclusive group makes Bootstrap
            // ignore show(). Wait before opening the next panel, outside in.
            const accordion = collapse.closest('.accordion') ?? collapse;
            await waitForAccordionTransitions(accordion, signal);
            if (signal.aborted) return;

            if (!collapse.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
                // Closing siblings also affect the target's final position.
                await waitForAccordionTransitions(accordion, signal);
                // Respect a prevented show.bs.collapse event.
                if (!collapse.classList.contains('show')) return;
            }
        }

        if (!signal.aborted) target.scrollIntoView();
    };

    document.addEventListener('DOMContentLoaded', revealHashTarget);
    window.addEventListener('hashchange', revealHashTarget);

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || link.hasAttribute('data-bs-toggle')) return;
        if (link.target && link.target !== '_self') return;

        // Following the current fragment again does not emit hashchange.
        if (link.hash && link.href === window.location.href) revealHashTarget();
    });
})();
