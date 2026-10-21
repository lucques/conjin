(() => {
    const setExpanded = (collapse, expanded) => {
        collapse.classList.toggle('show', expanded);

        const button = collapse.closest('.accordion-item')
            ?.querySelector('[data-bs-toggle="collapse"]');
        if (button) {
            button.classList.toggle('collapsed', !expanded);
            button.setAttribute('aria-expanded', String(expanded));
        }

        collapse.dispatchEvent(new Event(
            expanded ? 'shown.bs.collapse' : 'hidden.bs.collapse'
        ));
    };

    const toggleExpanded = (collapse, opening) => {
        if (opening) {
            const parentSelector = collapse.getAttribute('data-bs-parent');
            const parent = parentSelector?.startsWith('#')
                ? document.getElementById(parentSelector.slice(1))
                : null;
            parent?.querySelectorAll('.accordion-collapse.show').forEach(other => {
                if (other !== collapse && other.getAttribute('data-bs-parent') === parentSelector) {
                    setExpanded(other, false);
                }
            });
        }

        setExpanded(collapse, opening);
    };

    const revealHashTarget = () => {
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

        collapses.forEach(collapse => {
            if (!collapse.classList.contains('show')) toggleExpanded(collapse, true);
        });
        target.scrollIntoView();
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.accordion-collapse').forEach(collapse => {
            setExpanded(collapse, collapse.classList.contains('show'));
        });
        revealHashTarget();
    });
    window.addEventListener('hashchange', revealHashTarget);

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || link.hasAttribute('data-bs-toggle')) return;
        if (link.target && link.target !== '_self') return;

        // Following the current fragment again does not emit hashchange.
        if (link.hash && link.href === window.location.href) revealHashTarget();
    });

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-bs-toggle="collapse"]');
        if (!button) return;

        const selector = button.getAttribute('data-bs-target');
        const collapse = selector?.startsWith('#')
            ? document.getElementById(selector.slice(1))
            : null;
        if (!collapse?.classList.contains('accordion-collapse')) return;

        toggleExpanded(collapse, !collapse.classList.contains('show'));
    });
})();
