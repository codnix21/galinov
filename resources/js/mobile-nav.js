/**
 * Мобильное меню: открытие, закрытие по фону, Escape и при переходе по ссылке.
 */
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mobileNavBtn');
    const panel = document.getElementById('mobileNav');
    const backdrop = document.getElementById('mobileNavBackdrop');

    if (!btn || !panel || !backdrop) {
        return;
    }

    const setOpen = (open) => {
        panel.classList.toggle('hidden', !open);
        backdrop.classList.toggle('hidden', !open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('overflow-hidden', open);
    };

    btn.addEventListener('click', () => {
        setOpen(panel.classList.contains('hidden'));
    });

    backdrop.addEventListener('click', () => setOpen(false));

    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            setOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            setOpen(false);
        }
    });
});
