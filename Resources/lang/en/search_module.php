<?php

return [
    'admin' => [
        'menu' => 'Search',
        'settings' => 'Search Settings',
        'settings_description' => 'Manage global search sources and parameters',
        'enabled' => 'Enable search',
        'enabled_help' => 'Global toggle for site-wide search',
        'only_authenticated' => 'Only for authenticated users',
        'only_authenticated_help' => 'If enabled — search is visible only to logged-in users. If disabled — guests can use search.',
        'min_length' => 'Minimum query length',
        'min_length_help' => 'Minimum characters to start searching (0 = no limit)',
        'limit' => 'Results limit',
        'limit_help' => 'Default maximum number of results',
        'saved' => 'Search settings saved.',
        'sections' => [
            'general' => 'General Settings',
            'providers' => 'Search Providers',
            'providers_desc' => 'Manage data sources for global search. Modules can add their own providers automatically.',
        ],
        'table' => [
            'provider' => 'Provider',
            'key' => 'Key',
            'status' => 'Status',
            'enabled' => 'On',
            'disabled' => 'Off',
        ],
    ],
    'providers' => [
        'users' => 'Users',
        'users_desc' => 'Search user profiles',
        'pages' => 'Pages',
        'pages_desc' => 'Search static site pages',
        'navigation' => 'Navigation',
        'navigation_desc' => 'Search menu items',
    ],
    'ui' => [
        'search' => 'Search',
        'placeholder' => 'Search the site...',
        'start_typing' => 'Start typing to search',
        'min_chars' => 'Minimum :count characters',
        'no_results' => 'No results found',
        'try_different' => 'Try a different query or filter',
        'unavailable' => 'Search is temporarily unavailable',
        'unavailable_desc' => 'Please try again in a moment',
        'cancel' => 'Cancel',
        'filters' => [
            'all' => 'All',
        ],
        'hints' => [
            'navigate' => 'navigate',
            'select' => 'select',
            'filter' => 'filter',
            'close' => 'close',
        ],
    ],
];
