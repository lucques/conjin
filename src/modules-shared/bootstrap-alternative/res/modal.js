(() => {
    const previousFocus = new WeakMap();
    const originalPositions = new WeakMap();

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    const getTarget = trigger => {
        const selector = trigger.getAttribute('data-bs-target');
        return selector?.startsWith('#')
            ? document.getElementById(selector.slice(1))
            : null;
    };

    const getOpenModal = () => document.querySelector('.modal.show');

    const resetModalForms = modal => {
        modal.querySelectorAll('form').forEach(form => {
            form.reset();
            form.querySelectorAll('select').forEach(select => select.tomselect?.sync?.());
        });
    };

    const closeModal = (modal, resetForms = false) => {
        if (!modal?.classList.contains('show')) return;
        if (!modal.dispatchEvent(new Event('hide.bs.modal', { cancelable: true }))) return;

        if (resetForms) resetModalForms(modal);

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        modal.removeAttribute('role');
        document.querySelector(`.modal-backdrop[data-modal-id="${CSS.escape(modal.id)}"]`)?.remove();

        if (!getOpenModal()) document.body.classList.remove('modal-open');
        previousFocus.get(modal)?.focus();
        previousFocus.delete(modal);

        const placeholder = originalPositions.get(modal);
        if (placeholder?.parentNode) {
            placeholder.replaceWith(modal);
            originalPositions.delete(modal);
        }

        modal.dispatchEvent(new Event('hidden.bs.modal'));
    };

    const openModal = (modal, trigger) => {
        if (!modal?.classList.contains('modal') || modal.classList.contains('show')) return;
        if (!modal.dispatchEvent(new Event('show.bs.modal', { cancelable: true }))) return;

        const alreadyOpen = getOpenModal();
        if (alreadyOpen) closeModal(alreadyOpen);

        previousFocus.set(modal, trigger ?? document.activeElement);

        if (modal.parentElement !== document.body) {
            const placeholder = document.createComment(`modal ${modal.id}`);
            modal.before(placeholder);
            originalPositions.set(modal, placeholder);
            document.body.append(modal);
        }

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open');

        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.dataset.modalId = modal.id;
        backdrop.addEventListener('click', () => closeModal(modal));
        document.body.append(backdrop);

        modal.focus();
        modal.dispatchEvent(new Event('shown.bs.modal'));
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-bs-toggle="modal"]');
        if (trigger) {
            if (trigger.tagName === 'A') event.preventDefault();
            openModal(getTarget(trigger), trigger);
            return;
        }

        const dismiss = event.target.closest('[data-bs-dismiss="modal"]');
        if (dismiss) closeModal(dismiss.closest('.modal'), true);
    });

    document.addEventListener('keydown', event => {
        const modal = getOpenModal();
        if (!modal) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal(modal);
            return;
        }

        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll(focusableSelector)]
            .filter(element => element.getClientRects().length > 0);
        if (focusable.length === 0) {
            event.preventDefault();
            modal.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        }
        else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
