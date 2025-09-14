(() => {
    const getTarget = trigger => {
        const selector = trigger.getAttribute('data-bs-target');
        return selector?.startsWith('#')
            ? document.getElementById(selector.slice(1))
            : null;
    };

    const setExpanded = (collapse, expanded) => {
        const eventName = expanded ? 'show.bs.collapse' : 'hide.bs.collapse';
        if (!collapse.dispatchEvent(new Event(eventName, { cancelable: true }))) return;

        collapse.classList.toggle('show', expanded);
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(trigger => {
            if (getTarget(trigger) !== collapse) return;
            trigger.classList.toggle('collapsed', !expanded);
            trigger.setAttribute('aria-expanded', String(expanded));
        });

        collapse.dispatchEvent(new Event(
            expanded ? 'shown.bs.collapse' : 'hidden.bs.collapse'
        ));
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.collapse:not(.accordion-collapse)').forEach(collapse => {
            const expanded = collapse.classList.contains('show');
            document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(trigger => {
                if (getTarget(trigger) !== collapse) return;
                trigger.classList.toggle('collapsed', !expanded);
                trigger.setAttribute('aria-expanded', String(expanded));
            });
        });
    });

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-bs-toggle="collapse"]');
        if (!trigger) return;

        const collapse = getTarget(trigger);
        if (!collapse || collapse.classList.contains('accordion-collapse')) return;

        if (trigger.tagName === 'A') event.preventDefault();
        setExpanded(collapse, !collapse.classList.contains('show'));
    });
})();
