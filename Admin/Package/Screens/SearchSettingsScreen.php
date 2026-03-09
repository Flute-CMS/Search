<?php

namespace Flute\Modules\Search\Admin\Package\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\Search\Services\SearchRegistry;
use Throwable;

class SearchSettingsScreen extends Screen
{
    public ?string $name = 'search_module.admin.settings';

    public ?string $description = 'search_module.admin.settings_description';

    public ?string $permission = 'admin.search';

    public array $providers = [];

    public function mount(): void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('search_module.admin.menu'))
            ->add(__('search_module.admin.settings'));

        $this->providers = $this->getProviders();
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('save'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::split([
                LayoutFactory::block([
                    LayoutFactory::field(
                        Toggle::make('enabled')
                            ->checked(filter_var(config('search.enabled', true), FILTER_VALIDATE_BOOLEAN))
                    )
                        ->label(__('search_module.admin.enabled'))
                        ->small(__('search_module.admin.enabled_help')),

                    LayoutFactory::field(
                        Toggle::make('only_authenticated')
                            ->checked(filter_var(config('search.only_authenticated', true), FILTER_VALIDATE_BOOLEAN))
                    )
                        ->label(__('search_module.admin.only_authenticated'))
                        ->small(__('search_module.admin.only_authenticated_help')),

                    LayoutFactory::field(
                        Input::make('min_length')
                            ->type('number')
                            ->value((int) config('search.min_length', 2))
                            ->min(0)
                            ->max(64)
                    )
                        ->label(__('search_module.admin.min_length'))
                        ->small(__('search_module.admin.min_length_help')),

                    LayoutFactory::field(
                        Input::make('limit')
                            ->type('number')
                            ->value((int) config('search.limit', 20))
                            ->min(1)
                            ->max(100)
                    )
                        ->label(__('search_module.admin.limit'))
                        ->small(__('search_module.admin.limit_help')),
                ])->title(__('search_module.admin.sections.general')),

                LayoutFactory::table('providers', [
                    TD::make('icon', '')
                        ->width('50px')
                        ->cantHide()
                        ->render(static fn (array $row) => $row['iconHtml'] ?? ''),

                    TD::make('title', __('search_module.admin.table.provider'))
                        ->minWidth('180px')
                        ->cantHide()
                        ->render(static fn (array $row) => '
                        <div>
                            <strong>' . e($row['title']) . '</strong>
                            <small class="d-block text-muted">' . e($row['description']) . '</small>
                        </div>
                    '),

                    TD::make('key', __('search_module.admin.table.key'))
                        ->minWidth('120px')
                        ->render(static fn (array $row) => '<code>' . e($row['key']) . '</code>'),

                    TD::make('status', __('search_module.admin.table.status'))
                        ->alignCenter()
                        ->width('120px')
                        ->render(static fn (array $row) => $row['enabled']
                            ? '<span class="badge success">' . __('search_module.admin.table.enabled') . '</span>'
                            : '<span class="badge error">' . __('search_module.admin.table.disabled') . '</span>'),

                    TD::make('toggle', __('search_module.admin.table.enabled'))
                        ->alignCenter()
                        ->width('100px')
                        ->cantHide()
                        ->render(static fn (array $row) => Toggle::make('providers[' . $row['key'] . ']')
                            ->checked($row['enabled'])
                            ->render()),
                ])
                    ->title(__('search_module.admin.sections.providers'))
                    ->description(__('search_module.admin.sections.providers_desc')),
            ])->ratio('40/60'),
        ];
    }

    public function getProviders(): array
    {
        /** @var SearchRegistry $registry */
        $registry = app(SearchRegistry::class);

        $providers = [];
        foreach ($registry->all() as $provider) {
            $icon = $provider->getIcon() ?: 'ph.regular.magnifying-glass';
            $providers[] = [
                'key' => $provider->getKey(),
                'title' => $provider->getTitle(),
                'description' => $provider->getDescription(),
                'icon' => $icon,
                'iconHtml' => $this->renderIconHtml($icon),
                'enabled' => $registry->isProviderEnabled($provider->getKey()),
            ];
        }

        return $providers;
    }

    public function save(): void
    {
        /** @var SearchRegistry $registry */
        $registry = app(SearchRegistry::class);

        $data = request()->input();
        $providersInput = is_array($data['providers'] ?? null) ? $data['providers'] : [];

        $providers = [];
        foreach ($registry->all() as $provider) {
            $key = $provider->getKey();
            $providers[$key] = filter_var($providersInput[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $config = [
            'enabled' => filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'only_authenticated' => filter_var($data['only_authenticated'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'min_length' => max(0, min(64, (int) ($data['min_length'] ?? 2))),
            'limit' => max(1, min(100, (int) ($data['limit'] ?? 20))),
            'providers' => $providers,
        ];

        fs()->updateConfig(module_path('Search', 'Resources/config/search.php'), $config);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(module_path('Search', 'Resources/config/search.php'), true);
        }

        config()->set('search', $config);
        $this->providers = $this->getProviders();

        $this->flashMessage(__('search_module.admin.saved'), 'success');
    }

    private function renderIconHtml(?string $path): string
    {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            return '';
        }

        try {
            $finder = app(\Flute\Core\Modules\Icons\Services\IconFinder::class);
            $svg = $finder->loadFile($path);
            if (!$svg) {
                return '';
            }

            $icon = new \Flute\Core\Modules\Icons\Icon($svg);
            $icon->setAttributes([
                'width' => '1.25rem',
                'height' => '1.25rem',
                'fill' => 'currentColor',
                'role' => 'img',
                'style' => 'color: var(--text-500);',
            ]);

            return (string) $icon;
        } catch (Throwable) {
            return '';
        }
    }
}
