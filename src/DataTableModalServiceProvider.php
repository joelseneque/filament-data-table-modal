<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Joelseneque\DataTableModal\Livewire\DataTableManager;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DataTableModalServiceProvider extends PackageServiceProvider
{
    public static string $name = 'data-table-modal';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews('data-table-modal')
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        Livewire::component('data-table-modal-manager', DataTableManager::class);

        $this->registerAssets();
    }

    protected function registerAssets(): void
    {
        // Assets are registered with FilamentAsset so they are published to the
        // application's /public directory via `php artisan filament:assets`.
        // Built files live in resources/dist (committed so path-repo consumers
        // need no build step). Registration is guarded so the package boots even
        // before a first asset build.
        if (! class_exists(FilamentAsset::class)) {
            return;
        }

        $assets = [];

        $js = __DIR__.'/../resources/dist/data-table-modal.js';
        if (file_exists($js)) {
            $assets[] = Js::make('data-table-modal', $js)
                ->loadedOnRequest();
        }

        $css = __DIR__.'/../resources/dist/data-table-modal.css';
        if (file_exists($css)) {
            $assets[] = Css::make('data-table-modal', $css)
                ->loadedOnRequest();
        }

        if ($assets !== []) {
            FilamentAsset::register($assets, package: 'joelseneque/data-table-modal');
        }
    }
}
