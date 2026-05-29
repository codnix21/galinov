/**
 * DaData: город — только название НП; адрес — улица и дом (без города в строке).
 * Координаты выбранного адреса пишутся в скрытые поля geo_shirota / geo_dolgota.
 */

function dadataCityKladr(data) {
    return (
        data.city_kladr_id ||
        data.settlement_kladr_id ||
        ''
    ).toString();
}

function dadataCityFias(data) {
    return (
        data.city_fias_id ||
        data.settlement_fias_id ||
        ''
    ).toString();
}

/** Текст в поле «Город» — без региона в значении */
function dadataCityInputValue(data) {
    const v =
        data.city ||
        data.settlement ||
        (data.city_with_type || '')
            .replace(/^г\.?\s+/i, '')
            .trim() ||
        (data.settlement_with_type || '')
            .replace(/^(г|пгт|п|с|д|дер)\.?\s+/i, '')
            .trim() ||
        (data.area_with_type || '')
            .replace(/^(р-н|район)\.?\s+/i, '')
            .trim();
    if (v) {
        return v;
    }
    // Москва, СПб и др.: иногда в ответе только region
    if (data.region && ['г', 'город'].includes(data.region_type)) {
        return String(data.region)
            .replace(/^г\.?\s+/i, '')
            .trim();
    }
    return '';
}

/** Значение города для поля формы: из data или из подписи подсказки */
function dadataCityValueFromSuggestion(suggestion) {
    const data = suggestion.data || {};
    let name = dadataCityInputValue(data);
    if (name) {
        return name;
    }
    const label = dadataCityListLabel(suggestion);
    if (label) {
        return label.split(' — ')[0].trim();
    }
    const raw = suggestion.value || suggestion.unrestricted_value || '';
    return raw.split(',')[0].trim();
}

/** Подпись в списке городов — для различения одноимённых */
function dadataCityListLabel(suggestion) {
    const data = suggestion.data || {};
    const name =
        dadataCityInputValue(data) ||
        (suggestion.value || '').split(',')[0].trim();
    const reg = data.region_with_type || '';
    if (reg && data.city && !String(data.city).includes(reg)) {
        return `${name} — ${reg}`;
    }
    return name || suggestion.value || '';
}

/** Улица, дом, корпус — без населённого пункта */
function dadataStreetHouseValue(data) {
    const parts = [];
    if (data.street_with_type) {
        parts.push(data.street_with_type);
    } else if (data.street) {
        parts.push(data.street);
    }
    if (data.house) {
        const ht = (data.house_type || 'д').replace(/\.$/, '');
        parts.push(`${ht} ${data.house}`.trim());
    }
    if (data.block) {
        parts.push(`к ${data.block}`);
    }
    if (parts.length > 0) {
        return parts.join(', ');
    }
    return (data.street_address || data.result || '').trim();
}

function suggestionValueWithoutCity(data, suggestion) {
    const full = suggestion.unrestricted_value || suggestion.value || '';
    const city =
        data.city_with_type ||
        data.settlement_with_type ||
        data.city ||
        data.settlement ||
        '';
    if (city && full.includes(',')) {
        const idx = full.indexOf(city);
        if (idx >= 0) {
            const rest = full.slice(idx + city.length).replace(/^[,\s]+/, '');
            return rest;
        }
    }
    return '';
}

function dadataStreetHouseListLabel(suggestion) {
    const data = suggestion.data || {};
    const line = dadataStreetHouseValue(data);
    if (line) {
        return line;
    }
    return (
        suggestionValueWithoutCity(data, suggestion) ||
        suggestion.value ||
        ''
    );
}

// Запись широты/долготы в скрытые поля формы объявления
function setGeoHidden(lat, lon) {
    const latEl = document.querySelector('input[name="geo_shirota"]');
    const lonEl = document.querySelector('input[name="geo_dolgota"]');
    if (latEl) {
        latEl.value = lat != null && lat !== '' ? String(lat) : '';
    }
    if (lonEl) {
        lonEl.value = lon != null && lon !== '' ? String(lon) : '';
    }
}

// Выпадающий список подсказок у поля ввода
class DaDataAutocomplete {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            url: options.url || '/api/dadata/address',
            minLength: options.minLength || 3,
            delay: options.delay || 300,
            mode: options.mode || 'default',
            onSelect: options.onSelect || null,
            getExtraQuery: options.getExtraQuery || null,
            ...options,
        };

        this.suggestionsList = null;
        this.currentSuggestions = [];
        this.selectedIndex = -1;
        this.timeoutId = null;

        this.init();
    }

    init() {
        const parent = this.input.parentNode;
        if (window.getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }

        this.suggestionsList = document.createElement('ul');
        this.suggestionsList.className = 'dadata-suggestions';
        this.suggestionsList.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            list-style: none;
            margin: 0;
            padding: 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;

        parent.appendChild(this.suggestionsList);

        this.input.addEventListener('input', () => this.handleInput());
        this.input.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.input.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 200);
        });

        this.addStyles();
    }

    addStyles() {
        if (document.getElementById('dadata-autocomplete-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'dadata-autocomplete-styles';
        style.textContent = `
            .dadata-suggestions li {
                padding: 10px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
            }
            .dadata-suggestions li:hover,
            .dadata-suggestions li.selected {
                background-color: #f0f0f0;
            }
            .dadata-suggestions li:last-child {
                border-bottom: none;
            }
        `;
        document.head.appendChild(style);
    }

    handleInput() {
        if (this.options.mode === 'address') {
            setGeoHidden('', '');
        }

        const value = this.input.value.trim();

        if (this.options.mode === 'city') {
            delete this.input.dataset.cityKladrId;
            delete this.input.dataset.cityFiasId;
        }

        if (value.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }

        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }

        this.timeoutId = setTimeout(() => {
            this.fetchSuggestions(value);
        }, this.options.delay);
    }

    buildUrl(query) {
        let url = `${this.options.url}?query=${encodeURIComponent(query)}`;
        if (typeof this.options.getExtraQuery === 'function') {
            url += this.options.getExtraQuery();
        }
        return url;
    }

    async fetchSuggestions(query) {
        try {
            const url = this.buildUrl(query);
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }

            const data = await response.json();
            this.currentSuggestions = data;
            this.showSuggestions(data);
        } catch {
            this.hideSuggestions();
        }
    }

    showSuggestions(suggestions) {
        if (suggestions.error) {
            this.hideSuggestions();
            return;
        }

        if (!Array.isArray(suggestions) || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }

        this.suggestionsList.innerHTML = '';

        suggestions.forEach((suggestion, index) => {
            const li = document.createElement('li');
            if (this.options.mode === 'city') {
                li.textContent = dadataCityListLabel(suggestion);
            } else if (this.options.mode === 'address') {
                li.textContent = dadataStreetHouseListLabel(suggestion);
            } else {
                li.textContent =
                    suggestion.value || suggestion.unrestricted_value || '';
            }
            li.dataset.index = String(index);

            // mousedown + preventDefault: иначе blur очищает выбор до click (город «не выбран» при сохранении)
            li.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.selectSuggestion(suggestion);
            });

            this.suggestionsList.appendChild(li);
        });

        this.suggestionsList.style.display = 'block';
        this.selectedIndex = -1;
    }

    hideSuggestions() {
        this.suggestionsList.style.display = 'none';
        this.selectedIndex = -1;
    }

    handleKeyDown(e) {
        if (!this.suggestionsList || this.suggestionsList.style.display === 'none') {
            return;
        }

        const items = this.suggestionsList.querySelectorAll('li');

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection(items);
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection(items);
                break;

            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && this.currentSuggestions[this.selectedIndex]) {
                    this.selectSuggestion(this.currentSuggestions[this.selectedIndex]);
                }
                break;

            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }

    updateSelection(items) {
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    selectSuggestion(suggestion) {
        const data = suggestion.data || {};

        if (this.options.mode === 'city') {
            const cityName = dadataCityValueFromSuggestion(suggestion);
            this.input.value = cityName;
            this.input.dataset.selectedCity = cityName;
            const kl = dadataCityKladr(data);
            const fi = dadataCityFias(data);
            if (kl) {
                this.input.dataset.cityKladrId = kl;
            } else {
                delete this.input.dataset.cityKladrId;
            }
            if (fi) {
                this.input.dataset.cityFiasId = fi;
            } else {
                delete this.input.dataset.cityFiasId;
            }
            const addr = document.getElementById('adres_ulitsy');
            if (addr) {
                addr.value = '';
            }
            setGeoHidden('', '');
        } else if (this.options.mode === 'address') {
            let line = dadataStreetHouseValue(data);
            if (!line) {
                line =
                    suggestionValueWithoutCity(data, suggestion) ||
                    suggestion.value ||
                    '';
            }
            this.input.value = line.trim();
            const lat = data.geo_lat;
            const lon = data.geo_lon;
            if (lat != null && lon != null && lat !== '' && lon !== '') {
                setGeoHidden(lat, lon);
            }
        } else {
            this.input.value =
                suggestion.value || suggestion.unrestricted_value || '';
        }

        this.hideSuggestions();

        if (this.options.onSelect) {
            this.options.onSelect(suggestion);
        }
    }
}

// Привязка к полям с классами .dadata-city и .dadata-address на странице
function initDaDataAutocomplete() {
    const addressInputs = document.querySelectorAll('.dadata-address');
    addressInputs.forEach((input) => {
        if (input.dataset.dadataInitialized === 'true') {
            return;
        }
        input.dataset.dadataInitialized = 'true';

        const url = input.dataset.url || '/api/dadata/address';

        new DaDataAutocomplete(input, {
            url,
            mode: 'address',
            minLength: 2,
            getExtraQuery: () => {
                const cityEl = document.getElementById('gorod');
                if (!cityEl) {
                    return '';
                }
                const ck = cityEl.dataset.cityKladrId || '';
                const cf = cityEl.dataset.cityFiasId || '';
                return `&city_kladr_id=${encodeURIComponent(ck)}&city_fias_id=${encodeURIComponent(cf)}`;
            },
        });
    });

    const cityInputs = document.querySelectorAll('.dadata-city');
    cityInputs.forEach((input) => {
        if (input.dataset.dadataInitialized === 'true') {
            return;
        }
        input.dataset.dadataInitialized = 'true';

        const url = input.dataset.url || '/api/dadata/city';

        new DaDataAutocomplete(input, {
            url,
            mode: 'city',
            minLength: 2,
        });

        input.addEventListener('input', () => {
            if (input.value.trim() !== (input.dataset.selectedCity || '')) {
                delete input.dataset.selectedCity;
            }
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        if (form.dataset.dadataSubmitBound === 'true') {
            return;
        }
        form.dataset.dadataSubmitBound = 'true';
        form.addEventListener('submit', () => {
            const cityEl = document.getElementById('gorod');
            if (!cityEl) {
                return;
            }
            if (!cityEl.value.trim() && cityEl.dataset.selectedCity) {
                cityEl.value = cityEl.dataset.selectedCity;
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDaDataAutocomplete);
} else {
    initDaDataAutocomplete();
}

window.initDaDataAutocomplete = initDaDataAutocomplete;
