/**
 * Точка входа фронтенда (Vite): подключает axios, Alpine.js и автодополнение адресов DaData.
 */
import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Интерактивные компоненты на страницах (формы, модалки и т.д.)
Alpine.start();

// Подсказки города и улицы через DaData
import './dadata-autocomplete.js';
import './contract-fio-search.js';
import './searchable-select.js';
import './mortgage-calculator.js';
import './mobile-nav.js';
import './toast.js';
import './confirm-modal.js';
import './form-validation.js';
import './house-fields-toggle.js';
