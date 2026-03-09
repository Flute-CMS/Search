<?php

return  [
    'enabled' => true,
    'only_authenticated' => false,
    'min_length' => 2,
    'limit' => 20,
    'providers' => [
        'users' => true,
        'pages' => true,
        'navigation' => true,
        'news' => true,
        'wiki' => true,
    ],
];
