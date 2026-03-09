<?php

namespace Flute\Modules\Search\Providers;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Core\Template\Template;
use Flute\Modules\Search\Admin\Package\SearchAdminPackage;
use Flute\Modules\Search\SearchProviders\NavigationSearchProvider;
use Flute\Modules\Search\SearchProviders\PagesSearchProvider;
use Flute\Modules\Search\SearchProviders\UsersSearchProvider;
use Flute\Modules\Search\Services\SearchRegistry;
use Flute\Modules\Search\Services\SearchService;

class SearchProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();

        $registry = $container->has(SearchRegistry::class)
            ? $container->get(SearchRegistry::class)
            : new SearchRegistry();

        $container->set(SearchRegistry::class, $registry);

        $registry->registerProvider(new UsersSearchProvider());
        $registry->registerProvider(new PagesSearchProvider());
        $registry->registerProvider(new NavigationSearchProvider());

        if (!$container->has(SearchService::class)) {
            $container->set(SearchService::class, \DI\autowire(SearchService::class));
        }

        $this->loadPackage(new SearchAdminPackage());

        $enabled = filter_var(config('search.enabled', true), FILTER_VALIDATE_BOOLEAN);
        $onlyAuthenticated = filter_var(config('search.only_authenticated', true), FILTER_VALIDATE_BOOLEAN);
        $canRenderForUser = !$onlyAuthenticated || (user()->isLoggedIn());

        if (!is_admin_path() && $enabled && $canRenderForUser) {
            $this->loadViews('Resources/views', 'global-search');
            $this->loadScss('Resources/assets/scss/main.scss');

            $template = $container->get(Template::class);

            if (user()->isLoggedIn()) {
                if (method_exists($template, 'prependTemplateToSection')) {
                    $template->prependTemplateToSection('navbar-actions', 'global-search::search-trigger');
                } else {
                    $template->prependToSection('navbar-actions', $template->render('global-search::search-trigger'));
                }
            } elseif (!config('search.only_authenticated', true)) {
                if (method_exists($template, 'prependTemplateToSection')) {
                    $template->prependTemplateToSection('navbar-actions-guest', 'global-search::search-trigger');
                } else {
                    $template->prependToSection('navbar-actions-guest', $template->render('global-search::search-trigger'));
                }
            }

            $template->prependToSection('footer', $template->render('global-search::search-modal'));
        }
    }

    public function register(\DI\Container $container): void
    {
        $registry = $container->has(SearchRegistry::class)
            ? $container->get(SearchRegistry::class)
            : new SearchRegistry();

        $container->set(SearchRegistry::class, $registry);

        $registry->registerProvider(new UsersSearchProvider());
        $registry->registerProvider(new PagesSearchProvider());
        $registry->registerProvider(new NavigationSearchProvider());

        $container->set(SearchService::class, \DI\autowire(SearchService::class));
    }
}
