<?php

namespace Flute\Modules\Search\Admin\Package;

use Flute\Admin\Support\AbstractAdminPackage;

class SearchAdminPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');
    }

    public function getPermissions(): array
    {
        return ['admin', 'admin.search'];
    }

    public function getMenuItems(): array
    {
        return [
            [
                'icon' => 'ph.bold.magnifying-glass-bold',
                'title' => __('search_module.admin.settings'),
                'url' => url('/admin/search-module/settings'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 101;
    }
}
