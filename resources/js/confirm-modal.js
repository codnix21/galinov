let pendingForm = null;

function getModal() {
    return document.getElementById('confirm-modal');
}

export function confirmAction(message, title = 'Подтвердите действие') {
    const modal = getModal();
    if (!modal) {
        return Promise.resolve(window.confirm(message));
    }

    return new Promise((resolve) => {
        document.getElementById('confirm-modal-title').textContent = title;
        document.getElementById('confirm-modal-message').textContent = message;

        const onOk = () => {
            cleanup();
            resolve(true);
        };
        const onCancel = () => {
            cleanup();
            resolve(false);
        };

        const cleanup = () => {
            modal.close();
            document.getElementById('confirm-modal-ok').removeEventListener('click', onOk);
            document.getElementById('confirm-modal-cancel').removeEventListener('click', onCancel);
        };

        document.getElementById('confirm-modal-ok').addEventListener('click', onOk);
        document.getElementById('confirm-modal-cancel').addEventListener('click', onCancel);
        modal.showModal();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                return;
            }
            e.preventDefault();
            const message = form.getAttribute('data-confirm') || 'Продолжить?';
            const title = form.getAttribute('data-confirm-title') || 'Подтвердите действие';
            const ok = await confirmAction(message, title);
            if (ok) {
                form.dataset.confirmed = '1';
                form.requestSubmit();
            }
        });
    });

    document.querySelectorAll('.delete-form').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const type = form.getAttribute('data-type') || 'элемент';
            const name = form.getAttribute('data-name') || '';
            const itemName = name ? ` «${name}»` : '';
            const ok = await confirmAction(
                `Удалить ${type}${itemName}? Это действие необратимо.`,
                'Удаление',
            );
            if (ok) {
                form.submit();
            }
        });
    });
});

window.confirmAction = confirmAction;
