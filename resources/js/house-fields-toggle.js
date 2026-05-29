/**
 * Показ блока «Параметры дома» только при tip = house.
 */
function syncHouseFieldsPanel() {
    const tipSelect = document.getElementById('tip')
        || document.querySelector('select[name="type"]')
        || document.querySelector('select[name="filters[type]"]');
    const isHouse = tipSelect?.value === 'house';

    document.querySelectorAll('[data-house-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isHouse);
    });

    document.querySelectorAll('[data-house-form-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isHouse);
        panel.querySelectorAll('input, select').forEach((el) => {
            el.disabled = !isHouse;
        });
    });
}

function initHouseFieldsToggle() {
    const tipSelect = document.getElementById('tip')
        || document.querySelector('select[name="type"]')
        || document.querySelector('select[name="filters[type]"]');
    if (!tipSelect) {
        return;
    }
    tipSelect.addEventListener('change', syncHouseFieldsPanel);
    syncHouseFieldsPanel();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHouseFieldsToggle);
} else {
    initHouseFieldsToggle();
}
