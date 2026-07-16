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

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.accordion-collapse').forEach(collapse => {
            setExpanded(collapse, collapse.classList.contains('show'));
        });
    });

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-bs-toggle="collapse"]');
        if (!button) return;

        const selector = button.getAttribute('data-bs-target');
        const collapse = selector?.startsWith('#')
            ? document.getElementById(selector.slice(1))
            : null;
        if (!collapse?.classList.contains('accordion-collapse')) return;

        const opening = !collapse.classList.contains('show');
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
    });
})();
