/**
 * HTML5-валидация: явный data-validate и все POST-формы (кроме исключений).
 */
function initFormValidation() {
    document.querySelectorAll('form[method="post"]').forEach((form) => {
        if (form.classList.contains('favorite-form') || form.hasAttribute('data-no-validate')) {
            return;
        }
        if (!form.hasAttribute('data-validate')) {
            form.setAttribute('data-validate', '');
        }
    });

    document.querySelectorAll('form[data-validate]').forEach((form) => {
        if (form.dataset.validateInit === '1') {
            return;
        }
        form.dataset.validateInit = '1';

        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    showFieldHint(firstInvalid);
                }
            }
        });

        form.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('invalid', () => showFieldHint(field));
            field.addEventListener('input', () => clearFieldHint(field));
            field.addEventListener('change', () => clearFieldHint(field));
        });
    });
}

function showFieldHint(field) {
    field.classList.add('border-red-500', 'ring-1', 'ring-red-200');
    const id = field.getAttribute('id');
    if (!id) {
        return;
    }
    let hint = document.querySelector(`[data-for="${id}"]`);
    if (!hint && field.validationMessage) {
        hint = document.createElement('p');
        hint.dataset.for = id;
        hint.className = 'mt-1 text-sm text-red-600';
        field.parentNode?.appendChild(hint);
    }
    if (hint) {
        hint.textContent = field.validationMessage || 'Проверьте значение поля';
    }
}

function clearFieldHint(field) {
    field.classList.remove('border-red-500', 'ring-1', 'ring-red-200');
    const id = field.getAttribute('id');
    if (!id) {
        return;
    }
    const hint = document.querySelector(`[data-for="${id}"]`);
    if (hint?.dataset.for === id) {
        hint.remove();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFormValidation);
} else {
    initFormValidation();
}
