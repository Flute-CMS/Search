(function () {
    'use strict';

    const SEARCH_ENDPOINT = 'api/search/global';
    const DEBOUNCE_MS = 150;
    const MIN_LENGTH = 2;

    let searchModal = null;
    let searchInput = null;
    let searchResults = null;
    let searchEmpty = null;
    let searchNoResults = null;
    let searchUnavailable = null;
    let searchSpinner = null;
    let searchClear = null;
    let searchFilters = null;
    let groupTemplate = null;
    let itemTemplate = null;

    let debounceTimer = null;
    let abortController = null;
    let selectedIndex = -1;
    let flatItems = [];
    let currentFilter = 'all';
    let lastResults = [];
    let availableProviders = [];
    let providersLoaded = false;

    function init() {
        searchModal = document.getElementById('global-search');
        if (!searchModal) return;

        searchInput = document.getElementById('global-search-input');
        searchResults = document.getElementById('global-search-results');
        searchEmpty = document.getElementById('global-search-empty');
        searchNoResults = document.getElementById('global-search-no-results');
        searchUnavailable = document.getElementById('global-search-unavailable');
        searchSpinner = searchModal.querySelector('.global-search__spinner');
        searchClear = searchModal.querySelector('[data-global-search-clear]');
        searchFilters = document.getElementById('global-search-filters');
        groupTemplate = document.getElementById('global-search-group-template');
        itemTemplate = document.getElementById('global-search-item-template');

        document.querySelectorAll('[data-global-search-open]').forEach(el => {
            el.addEventListener('click', open);
        });

        document.querySelectorAll('[data-global-search-close]').forEach(el => {
            el.addEventListener('click', close);
        });

        searchClear?.addEventListener('click', clearInput);
        searchInput?.addEventListener('input', onInput);
        searchFilters?.addEventListener('click', onFilterClick);
        document.addEventListener('keydown', onKeyDown);

        loadProviders();
    }

    async function loadProviders() {
        if (providersLoaded) return;

        try {
            const response = await fetch(u('api/search/providers'), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                availableProviders = data.providers || [];
                providersLoaded = true;
                renderFilters();
            }
        } catch (err) {
            console.warn('Failed to load search providers:', err);
        }
    }

    function renderFilters() {
        if (!searchFilters) return;

        const existingAllBtn = searchFilters.querySelector('[data-filter="all"]');
        const allText = existingAllBtn?.textContent?.trim() || window.searchTranslations?.all || 'All';

        searchFilters.innerHTML = '';

        const allBtn = document.createElement('button');
        allBtn.type = 'button';
        allBtn.className = 'global-search__filter active';
        allBtn.dataset.filter = 'all';
        allBtn.textContent = allText;
        searchFilters.appendChild(allBtn);

        availableProviders.forEach(provider => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'global-search__filter';
            btn.dataset.filter = provider.key;
            btn.textContent = provider.title;
            searchFilters.appendChild(btn);
        });

        updateFiltersScroll();
    }

    function updateFiltersScroll() {
        if (!searchFilters) return;
        
        const activeBtn = searchFilters.querySelector('.global-search__filter.active');
        if (activeBtn) {
            activeBtn.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest', 
                inline: 'center' 
            });
        }
    }

    function open() {
        if (!searchModal) return;
        searchModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        setTimeout(() => searchInput?.focus(), 50);
        
        if (!providersLoaded) {
            loadProviders();
        }
    }

    function close(reset = true) {
        if (!searchModal) return;
        searchModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
        cancelSearch();
        if (reset) {
            clearInput();
        }
    }

    function cancelSearch() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        if (abortController) {
            abortController.abort();
            abortController = null;
        }
        setLoading(false);
    }

    function clearInput() {
        cancelSearch();
        if (searchInput) searchInput.value = '';
        showEmpty();
        selectedIndex = -1;
        flatItems = [];
        lastResults = [];
        updateClearButton();
    }

    function updateClearButton() {
        const hasValue = searchInput && searchInput.value.length > 0;
        searchClear?.classList.toggle('active', hasValue);
    }

    function showEmpty() {
        if (searchEmpty) searchEmpty.style.display = '';
        if (searchNoResults) searchNoResults.style.display = 'none';
        if (searchUnavailable) searchUnavailable.style.display = 'none';
        if (searchResults) searchResults.innerHTML = '';
    }

    function showNoResults() {
        if (searchEmpty) searchEmpty.style.display = 'none';
        if (searchNoResults) searchNoResults.style.display = '';
        if (searchUnavailable) searchUnavailable.style.display = 'none';
        if (searchResults) searchResults.innerHTML = '';
    }

    function showUnavailable(message) {
        if (searchEmpty) searchEmpty.style.display = 'none';
        if (searchNoResults) searchNoResults.style.display = 'none';
        if (searchUnavailable) searchUnavailable.style.display = '';
        if (searchResults) searchResults.innerHTML = '';

        if (message && searchUnavailable) {
            const p = searchUnavailable.querySelector('p');
            if (p) p.textContent = message;
        }
    }

    function showResults() {
        if (searchEmpty) searchEmpty.style.display = 'none';
        if (searchNoResults) searchNoResults.style.display = 'none';
        if (searchUnavailable) searchUnavailable.style.display = 'none';
    }

    function setLoading(loading) {
        searchSpinner?.classList.toggle('active', loading);
    }

    function onInput() {
        updateClearButton();

        const value = (searchInput?.value || '').trim();

        if (debounceTimer) clearTimeout(debounceTimer);
        if (abortController) abortController.abort();

        if (value.length < MIN_LENGTH) {
            showEmpty();
            selectedIndex = -1;
            flatItems = [];
            setLoading(false);
            return;
        }

        debounceTimer = setTimeout(() => doSearch(value), DEBOUNCE_MS);
    }

    async function doSearch(query) {
        setLoading(true);

        abortController = new AbortController();

        try {
            const params = new URLSearchParams({ q: query });
            if (currentFilter !== 'all') {
                params.set('providers', currentFilter);
            }
            
            const response = await fetch(u(SEARCH_ENDPOINT + '?' + params.toString()), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: abortController.signal
            });

            let data = null;
            try {
                data = await response.json();
            } catch (e) {
                data = null;
            }

            if (!response.ok) {
                showUnavailable((data && data.error) ? data.error : null);
                return;
            }

            if (data && data.error) {
                showUnavailable(data.error);
                return;
            }

            lastResults = data.results || [];
            
            renderResults(lastResults);
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Search error:', err);
                showUnavailable(null);
            }
        } finally {
            setLoading(false);
        }
    }

    function onFilterClick(e) {
        const btn = e.target.closest('[data-filter]');
        if (!btn) return;

        const filter = btn.dataset.filter;
        if (filter === currentFilter) return;

        searchFilters.querySelectorAll('[data-filter]').forEach(el => {
            el.classList.toggle('active', el.dataset.filter === filter);
        });

        currentFilter = filter;
        updateFiltersScroll();
        
        const value = (searchInput?.value || '').trim();
        if (value.length >= MIN_LENGTH) {
            if (debounceTimer) clearTimeout(debounceTimer);
            if (abortController) abortController.abort();
            doSearch(value);
        }
        
        searchInput?.focus();
    }

    function renderResults(results) {
        if (!results || results.length === 0) {
            showNoResults();
            flatItems = [];
            selectedIndex = -1;
            return;
        }

        showResults();

        const grouped = {};
        results.forEach(item => {
            const key = item.providerTitle || item.provider || 'Results';
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(item);
        });

        searchResults.innerHTML = '';
        flatItems = [];

        Object.entries(grouped).forEach(([title, items]) => {
            const groupEl = groupTemplate.content.cloneNode(true);
            const groupDiv = groupEl.querySelector('[data-group]');
            groupDiv.querySelector('.global-search__group-title').textContent = title;

            const list = groupDiv.querySelector('.global-search__group-items');

            items.forEach(item => {
                const itemEl = itemTemplate.content.cloneNode(true);
                const li = itemEl.querySelector('[data-item]');
                const link = li.querySelector('.global-search__item-link');
                const iconWrap = li.querySelector('.global-search__item-icon');
                const titleEl = li.querySelector('.global-search__item-title');
                const subtitleEl = li.querySelector('.global-search__item-subtitle');

                link.href = item.url || '#';
                link.setAttribute('hx-boost', 'true');
                link.setAttribute('hx-push-url', 'true');
                link.setAttribute('hx-target', '#main');
                link.setAttribute('hx-swap', 'outerHTML transition:true');
                link.addEventListener('click', () => close(false), { capture: true });
                if (typeof htmx !== 'undefined' && htmx.process) {
                    htmx.process(link);
                }

                if (item.image) {
                    iconWrap.innerHTML = '<img src="' + escapeHtml(item.image) + '" alt="" loading="lazy" />';
                } else if (item.iconHtml) {
                    iconWrap.innerHTML = item.iconHtml;
                } else {
                    iconWrap.classList.add('global-search__item-icon--hidden');
                }

                titleEl.innerHTML = highlightMatch(item.title || '', searchInput?.value || '');
                subtitleEl.textContent = item.subtitle || '';

                flatItems.push({ el: li, link, data: item });
                list.appendChild(li);
            });

            searchResults.appendChild(groupDiv);
        });

        selectedIndex = 0;
        updateSelection();
    }

    function highlightMatch(text, query) {
        if (!query || query.length < 2) return escapeHtml(text);
        
        const escaped = escapeHtml(text);
        const queryEscaped = escapeHtml(query);
        const regex = new RegExp('(' + queryEscaped.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        
        return escaped.replace(regex, '<mark>$1</mark>');
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function processIcons(container) {
        if (typeof htmx !== 'undefined' && htmx.process) {
            htmx.process(container);
        }
    }

    function updateSelection() {
        flatItems.forEach((item, i) => {
            item.el.classList.toggle('active', i === selectedIndex);
        });

        if (selectedIndex >= 0 && flatItems[selectedIndex]) {
            flatItems[selectedIndex].el.scrollIntoView({ 
                block: 'nearest', 
                behavior: 'smooth' 
            });
        }
    }

    function onKeyDown(e) {
        const isOpen = searchModal?.getAttribute('aria-hidden') === 'false';

        // Layout-independent: relies on physical key position (works for RU/FR/DE/etc.)
        if ((e.metaKey || e.ctrlKey) && e.code === 'KeyK') {
            e.preventDefault();
            if (isOpen) {
                close();
            } else {
                open();
            }
            return;
        }

        if (!isOpen) return;

        switch (e.key) {
            case 'Escape':
                e.preventDefault();
                close();
                break;

            case 'ArrowDown':
                e.preventDefault();
                if (flatItems.length > 0) {
                    selectedIndex = (selectedIndex + 1) % flatItems.length;
                    updateSelection();
                }
                break;

            case 'ArrowUp':
                e.preventDefault();
                if (flatItems.length > 0) {
                    selectedIndex = (selectedIndex - 1 + flatItems.length) % flatItems.length;
                    updateSelection();
                }
                break;

            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0 && flatItems[selectedIndex]?.link) {
                    flatItems[selectedIndex].link.click();
                }
                break;

            case 'Tab':
                e.preventDefault();
                cycleFilter(e.shiftKey ? -1 : 1);
                break;
        }
    }

    function cycleFilter(direction) {
        const filterButtons = searchFilters?.querySelectorAll('[data-filter]');
        if (!filterButtons || filterButtons.length === 0) return;

        const filters = Array.from(filterButtons).map(el => el.dataset.filter);
        const currentIdx = filters.indexOf(currentFilter);
        const nextIdx = (currentIdx + direction + filters.length) % filters.length;
        const nextFilter = filters[nextIdx];

        filterButtons.forEach(el => {
            el.classList.toggle('active', el.dataset.filter === nextFilter);
        });

        currentFilter = nextFilter;
        updateFiltersScroll();
        
        const value = (searchInput?.value || '').trim();
        if (value.length >= MIN_LENGTH) {
            if (debounceTimer) clearTimeout(debounceTimer);
            if (abortController) abortController.abort();
            doSearch(value);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('htmx:afterSwap', init);
})();
