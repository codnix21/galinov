{{-- Скрипт поисковых полей (работает без пересборки Vite) --}}
@once
@push('scripts')
<script>
(function() {
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initFioSearchSelects() {
        document.querySelectorAll('.fio-search-select').forEach(function(wrap) {
            if (wrap.dataset.fioInitialized === '1') return;
            wrap.dataset.fioInitialized = '1';

            var hidden = wrap.querySelector('input[type="hidden"]');
            var input = wrap.querySelector('.fio-search-input');
            var list = wrap.querySelector('.fio-search-results');
            var searchUrl = wrap.dataset.searchUrl || '';
            var initialJson = wrap.querySelector('.fio-search-initial');
            var items = [];
            try { items = JSON.parse(initialJson ? initialJson.textContent : '[]'); } catch (e) { items = []; }
            var debounceTimer = null;
            var activeIndex = -1;

            function renderList(filtered) {
                list.innerHTML = '';
                if (!filtered.length) {
                    var empty = document.createElement('li');
                    empty.className = 'px-4 py-3 text-slate-500';
                    empty.textContent = 'Ничего не найдено';
                    list.appendChild(empty);
                } else {
                    filtered.forEach(function(item) {
                        var li = document.createElement('li');
                        li.className = 'px-4 py-2.5 cursor-pointer hover:bg-brand-50 border-b border-slate-100 last:border-0';
                        li.setAttribute('role', 'option');
                        li.innerHTML = '<span class="font-medium text-slate-900 block">' + escapeHtml(item.label) + '</span>' +
                            (item.hint ? '<span class="text-xs text-slate-500">' + escapeHtml(item.hint) + '</span>' : '');
                        li.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            hidden.value = item.value;
                            input.value = item.label;
                            if (item.data) {
                                Object.keys(item.data).forEach(function(k) {
                                    hidden.dataset[k] = item.data[k];
                                });
                            }
                            hidden.dispatchEvent(new Event('change', { bubbles: true }));
                            if (hidden.id === 'nedvizhimost_id' && item.data) {
                                document.dispatchEvent(new CustomEvent('contract-property-selected', {
                                    detail: {
                                        operation: item.data.operation || null,
                                        owner_id: item.data.owner_id || null
                                    }
                                }));
                            }
                            list.classList.add('hidden');
                            input.setAttribute('aria-expanded', 'false');
                        });
                        list.appendChild(li);
                    });
                }
                list.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                activeIndex = -1;
            }

            var maxResults = 12;

            function filterLocal(q) {
                var query = q.trim().toLowerCase();
                if (!query) return items.slice(0, maxResults);
                return items.filter(function(item) {
                    return (item.label + ' ' + (item.hint || '')).toLowerCase().indexOf(query) !== -1;
                }).slice(0, maxResults);
            }

            function fetchRemote(q) {
                if (!searchUrl) {
                    renderList(filterLocal(q));
                    return;
                }
                var url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('q', q.trim());
                fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var remote = (data.items || []).slice(0, maxResults);
                    renderList(remote.length ? remote : filterLocal(q));
                })
                .catch(function() { renderList(filterLocal(q)); });
            }

            input.addEventListener('input', function() {
                hidden.value = '';
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() { fetchRemote(input.value); }, 200);
            });
            input.addEventListener('focus', function() { fetchRemote(input.value); });
            input.addEventListener('blur', function() {
                setTimeout(function() { list.classList.add('hidden'); input.setAttribute('aria-expanded', 'false'); }, 150);
            });
            input.addEventListener('keydown', function(e) {
                var options = list.querySelectorAll('li[role="option"]');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, options.length - 1);
                    if (options[activeIndex]) options[activeIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    if (options[activeIndex]) options[activeIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
                    e.preventDefault();
                    options[activeIndex].dispatchEvent(new Event('mousedown'));
                } else if (e.key === 'Escape') {
                    list.classList.add('hidden');
                }
            });
        });
    }

    window.initFioSearchSelects = initFioSearchSelects;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFioSearchSelects);
    } else {
        initFioSearchSelects();
    }
})();
</script>
@endpush
@endonce
