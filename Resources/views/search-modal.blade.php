<div class="global-search" id="global-search" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="global-search-title">
    <div class="global-search__backdrop" data-global-search-close></div>
    
    <div class="global-search__container">
        <header class="global-search__header">
            <div class="global-search__input-wrap">
                <x-icon path="ph.regular.magnifying-glass" class="global-search__input-icon" />
                <input 
                    type="text" 
                    class="global-search__input" 
                    id="global-search-input"
                    placeholder="{{ __('search_module.ui.placeholder') }}"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    aria-label="{{ __('search_module.ui.search') }}"
                />
                <div class="global-search__spinner" aria-hidden="true"></div>
                <button type="button" class="global-search__clear" data-global-search-clear aria-label="{{ __('def.clear') }}">
                    <x-icon path="ph.regular.x" />
                </button>
                <button type="button" class="global-search__close-mobile" data-global-search-close aria-label="{{ __('def.close') }}">
                    {{ __('search_module.ui.cancel') }}
                </button>
            </div>
            
            <div class="global-search__filters" id="global-search-filters">
                <button type="button" class="global-search__filter active" data-filter="all">
                    {{ __('search_module.ui.filters.all') }}
                </button>
            </div>
        </header>
        
        <div class="global-search__body" id="global-search-body">
            <div class="global-search__empty" id="global-search-empty">
                <x-icon path="ph.regular.magnifying-glass" />
                <p>{{ __('search_module.ui.start_typing') }}</p>
                <small>{{ __('search_module.ui.min_chars', ['count' => config('search.min_length', 2)]) }}</small>
            </div>
            
            <div class="global-search__no-results" id="global-search-no-results" style="display: none;">
                <x-icon path="ph.regular.magnifying-glass" />
                <p>{{ __('search_module.ui.no_results') }}</p>
                <small>{{ __('search_module.ui.try_different') }}</small>
            </div>

            <div class="global-search__no-results" id="global-search-unavailable" style="display: none;">
                <x-icon path="ph.regular.warning-circle" />
                <p>{{ __('search_module.ui.unavailable') }}</p>
                <small>{{ __('search_module.ui.unavailable_desc') }}</small>
            </div>
            
            <div class="global-search__results" id="global-search-results"></div>
        </div>
        
        <footer class="global-search__footer">
            <div class="global-search__hints">
                <span class="global-search__hint">
                    <kbd>↑</kbd><kbd>↓</kbd>
                    <span>{{ __('search_module.ui.hints.navigate') }}</span>
                </span>
                <span class="global-search__hint">
                    <kbd>↵</kbd>
                    <span>{{ __('search_module.ui.hints.select') }}</span>
                </span>
                <span class="global-search__hint">
                    <kbd>Tab</kbd>
                    <span>{{ __('search_module.ui.hints.filter') }}</span>
                </span>
                <span class="global-search__hint">
                    <kbd>Esc</kbd>
                    <span>{{ __('search_module.ui.hints.close') }}</span>
                </span>
            </div>
        </footer>
    </div>
</div>

<template id="global-search-group-template">
    <div class="global-search__group" data-group>
        <h4 class="global-search__group-title"></h4>
        <ul class="global-search__group-items"></ul>
    </div>
</template>

<template id="global-search-item-template">
    <li class="global-search__item" data-item>
        <a class="global-search__item-link" href="#">
            <div class="global-search__item-icon"></div>
            <div class="global-search__item-content">
                <span class="global-search__item-title"></span>
                <span class="global-search__item-subtitle"></span>
            </div>
            <x-icon path="ph.regular.arrow-right" class="global-search__item-arrow" />
        </a>
    </li>
</template>
