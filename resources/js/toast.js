const TOAST_STYLES = {
    success: { icon: '✓', classes: 'border-emerald-200 bg-emerald-50 text-emerald-900' },
    error: { icon: '✕', classes: 'border-red-200 bg-red-50 text-red-900' },
    warning: { icon: '⚠', classes: 'border-amber-200 bg-amber-50 text-amber-900' },
    info: { icon: 'ℹ', classes: 'border-brand-200 bg-brand-50 text-brand-900' },
};

function getToastStack() {
    let stack = document.getElementById('toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toast-stack';
        stack.className =
            'pointer-events-none fixed bottom-4 right-4 z-[100] flex max-w-sm flex-col gap-2 sm:bottom-6 sm:right-6';
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }
    return stack;
}

export function showToast(message, type = 'info', durationMs = 6000) {
    const stack = getToastStack();
    const style = TOAST_STYLES[type] || TOAST_STYLES.info;

    const el = document.createElement('div');
    el.className = `pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg ${style.classes}`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-current/20 text-sm font-bold">${style.icon}</span>
        <p class="flex-1 text-sm font-medium leading-snug pt-0.5">${escapeHtml(message)}</p>
        <button type="button" class="shrink-0 text-lg leading-none opacity-60 hover:opacity-100" aria-label="Закрыть">&times;</button>
    `;

    const close = () => {
        el.classList.add('opacity-0', 'translate-x-2', 'transition-all', 'duration-200');
        setTimeout(() => el.remove(), 200);
    };

    el.querySelector('button').addEventListener('click', close);
    stack.appendChild(el);

    if (durationMs > 0) {
        setTimeout(close, durationMs);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flash-toast]').forEach((node) => {
        showToast(node.dataset.message || '', node.dataset.type || 'info');
        node.remove();
    });
});

window.showToast = showToast;
