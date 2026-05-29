/**
 * Поиск в <select> (Tom Select) — только там, где много вариантов.
 * Исключения: .catalog-filters, .select-native, data-no-search, .fio-search-select.
 */
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.default.css';

const initialized = new WeakSet();

function shouldEnhance(select) {
    if (!(select instanceof HTMLSelectElement)) {
        return false;
    }
    if (select.dataset.noSearch !== undefined) {
        return false;
    }
    if (select.closest('.fio-search-select') || select.closest('.catalog-filters')) {
        return false;
    }
    if (select.classList.contains('select-native')) {
        return false;
    }
    if (initialized.has(select)) {
        return false;
    }
    if (select.options.length < 2 && !select.multiple) {
        return false;
    }

    // Короткие списки (тип, операция, статус) — обычный select
    if (!select.multiple && select.options.length <= 8) {
        return false;
    }

    return true;
}

function enhanceSelect(select) {
    if (!shouldEnhance(select)) {
        return;
    }

    initialized.add(select);

    const isMultiple = select.multiple;
    const plugins = isMultiple ? ['remove_button'] : [];

    new TomSelect(select, {
        plugins,
        create: false,
        maxOptions: 500,
        allowEmptyOption: true,
        placeholder: select.getAttribute('data-placeholder') || 'Введите для поиска…',
        render: {
            no_results: () => '<div class="no-results px-3 py-2 text-sm text-slate-500">Ничего не найдено</div>',
        },
        onInitialize() {
            select.classList.add('ts-hidden-accessible');
        },
    });
}

export function initSearchableSelects(root = document) {
    root.querySelectorAll('select').forEach(enhanceSelect);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initSearchableSelects());
} else {
    initSearchableSelects();
}
