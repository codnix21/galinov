/**
 * Показ блоков «Параметры дома» / «Земельный участок» в зависимости от типа объявления.
 */
function propertyTypeSelect() {
    return document.getElementById('catalog-type-select')
        || document.getElementById('tip')
        || document.querySelector('select[name="type"]')
        || document.querySelector('select[name="filters[type]"]');
}

function syncPropertyTypePanels() {
    const tipSelect = propertyTypeSelect();
    const tip = tipSelect?.value ?? '';
    const isHouse = tip === 'house';
    const isLand = tip === 'land';
    const isCommercial = tip === 'commercial';

    const setEnabled = (panel, enabled) => {
        panel.querySelectorAll('input, select, textarea').forEach((el) => {
            el.disabled = !enabled;
        });
    };

    document.querySelectorAll('[data-house-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isHouse);
        setEnabled(panel, isHouse);
    });

    document.querySelectorAll('[data-land-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isLand);
        setEnabled(panel, isLand);
    });

    document.querySelectorAll('[data-commercial-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isCommercial);
        setEnabled(panel, isCommercial);
    });

    document.querySelectorAll('[data-house-form-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isHouse);
        panel.querySelectorAll('input, select').forEach((el) => {
            el.disabled = !isHouse;
        });
    });

    document.querySelectorAll('[data-land-form-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isLand);
        panel.querySelectorAll('input, select').forEach((el) => {
            el.disabled = !isLand;
        });
    });

    document.querySelectorAll('[data-commercial-form-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', !isCommercial);
        panel.querySelectorAll('input, select').forEach((el) => {
            el.disabled = !isCommercial;
        });
    });

    document.querySelectorAll('[data-show-types]').forEach((block) => {
        const allowed = (block.dataset.showTypes || '')
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);
        const show = tip !== '' && allowed.includes(tip);
        block.classList.toggle('hidden', !show);
        setEnabled(block, show);
    });

    const typeHint = document.getElementById('catalog-type-hint');
    if (typeHint) {
        typeHint.classList.toggle('hidden', tip !== '');
    }
}

function initHouseFieldsToggle() {
    const tipSelect = propertyTypeSelect();
    if (!tipSelect) {
        return;
    }
    tipSelect.addEventListener('change', syncPropertyTypePanels);
    syncPropertyTypePanels();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHouseFieldsToggle);
} else {
    initHouseFieldsToggle();
}
