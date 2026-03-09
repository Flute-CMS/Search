<?php

namespace Flute\Modules\Search;

use Flute\Core\Database\Entities\Permission;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\Support\AbstractModuleInstaller;

class Installer extends AbstractModuleInstaller
{
    public function install(ModuleInformation &$module): bool
    {
        $permission = Permission::findOne(['name' => 'admin.search']);

        if (!$permission) {
            $permission = new Permission();
            $permission->name = 'admin.search';
            $permission->desc = 'search_module.admin.menu';
            $permission->save();
        }

        return true;
    }

    public function uninstall(ModuleInformation &$module): bool
    {
        $permission = Permission::findOne(['name' => 'admin.search']);

        if ($permission) {
            $permission->delete();
        }

        return true;
    }
}
