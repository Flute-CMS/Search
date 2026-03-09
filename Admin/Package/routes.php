<?php

use Flute\Core\Router\Router;
use Flute\Modules\Search\Admin\Package\Screens\SearchSettingsScreen;

Router::screen('/admin/search-module/settings', SearchSettingsScreen::class);
