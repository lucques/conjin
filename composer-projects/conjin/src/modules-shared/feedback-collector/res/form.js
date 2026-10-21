(() => {
    'use strict';

    const initialize = () => {
        document.querySelectorAll('form[data-feedback-collector]').forEach(form => {
            const synchronize = () => {
                // Parents precede descendants, so nested branches inherit their visibility.
                form.querySelectorAll('[data-follow-up-for]').forEach(branch => {
                    const control = document.getElementById(branch.dataset.followUpFor);
                    const active = control.checked && !control.closest('fieldset[disabled]');
                    branch.hidden = !active;
                    branch.disabled = !active;
                    control.setAttribute('aria-controls', branch.id ||= `${control.id}-follow-up`);
                    control.setAttribute('aria-expanded', active ? 'true' : 'false');
                });

                form.querySelectorAll('[data-feedback-question]').forEach(question => {
                    const required = question.dataset.required === '1' && !question.closest('fieldset[disabled]');
                    const inputs = [...question.querySelectorAll('input, textarea')]
                        .filter(input => input.closest('[data-feedback-question]') === question);
                    const checkboxes = inputs.filter(input => input.type === 'checkbox');
                    inputs.forEach(input => {
                        input.required = required && input.type !== 'checkbox';
                        input.setCustomValidity('');
                    });
                    if (required && checkboxes.length && !checkboxes.some(input => input.checked)) {
                        checkboxes[0].setCustomValidity('Bitte wählen Sie mindestens eine Antwort aus.');
                    }
                });
            };
            form.addEventListener('change', synchronize);
            window.addEventListener('pageshow', synchronize);
            synchronize();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    }
    else {
        initialize();
    }
})();
