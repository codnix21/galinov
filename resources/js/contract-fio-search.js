/**
 * Поисковые поля выбора (ФИО / объект) на форме создания договора.
 */
function initFioSearchSelects() {
    document.querySelectorAll('.fio-search-select').forEach((wrap) => {
        const hidden = wrap.querySelector('input[type="hidden"]');
        const input = wrap.querySelector('.fio-search-input');
        const list = wrap.querySelector('.fio-search-results');
        const searchUrl = wrap.dataset.searchUrl || '';
        const initialJson = wrap.querySelector('.fio-search-initial');
        let items = [];
        try {
            items = JSON.parse(initialJson?.textContent || '[]');
        } catch (_) {
            items = [];
        }
        let debounceTimer = null;
        let activeIndex = -1;

        function renderList(filtered) {
            list.innerHTML = '';
            if (!filtered.length) {
                const li = document.createElement('li');
                li.className = 'px-4 py-3 text-slate-500';
                li.textContent = 'Ничего не найдено';
                list.appendChild(li);
            } else {
                filtered.forEach((item, idx) => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-2.5 cursor-pointer hover:bg-brand-50 border-b border-slate-100 last:border-0';
                    li.setAttribute('role', 'option');
                    li.dataset.index = String(idx);
                    li.innerHTML = `<span class="font-medium text-slate-900 block">${escapeHtml(item.label)}</span>` +
                        (item.hint ? `<span class="text-xs text-slate-500">${escapeHtml(item.hint)}</span>` : '');
                    li.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectItem(item);
                    });
                    list.appendChild(li);
                });
            }
            list.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
            activeIndex = -1;
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function hideList() {
            list.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }

        function selectItem(item) {
            hidden.value = item.value;
            input.value = item.label;
            if (item.data) {
                Object.entries(item.data).forEach(([k, v]) => {
                    hidden.dataset[k] = v;
                });
            }
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            if (hidden.id === 'nedvizhimost_id' && item.data?.operation) {
                document.dispatchEvent(new CustomEvent('contract-property-selected', {
                    detail: { operation: item.data.operation },
                }));
            }
            hideList();
        }

        function filterLocal(q) {
            const query = q.trim().toLowerCase();
            if (!query) {
                return items.slice(0, 50);
            }
            return items.filter((item) => {
                const hay = `${item.label} ${item.hint || ''}`.toLowerCase();
                return hay.includes(query);
            }).slice(0, 50);
        }

        function fetchRemote(q) {
            if (!searchUrl || q.trim().length < 1) {
                renderList(filterLocal(q));
                return;
            }
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', q.trim());
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((r) => r.json())
                .then((data) => {
                    const remote = data.items || [];
                    renderList(remote.length ? remote : filterLocal(q));
                })
                .catch(() => renderList(filterLocal(q)));
        }

        function onInput() {
            hidden.value = '';
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchRemote(input.value), 200);
        }

        input.addEventListener('input', onInput);
        input.addEventListener('focus', () => fetchRemote(input.value));
        input.addEventListener('blur', () => setTimeout(hideList, 150));
        input.addEventListener('keydown', (e) => {
            const options = list.querySelectorAll('li[role="option"]');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, options.length - 1);
                options[activeIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                options[activeIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
                e.preventDefault();
                options[activeIndex].dispatchEvent(new Event('mousedown'));
            } else if (e.key === 'Escape') {
                hideList();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initFioSearchSelects);

export { initFioSearchSelects };
