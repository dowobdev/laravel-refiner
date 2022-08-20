<?php

namespace Dowob\Refiner;

use Dowob\Refiner\Console\Commands\RefinerMakeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RefinerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-refiner')
            ->hasConfigFile()
            ->hasCommand(RefinerMakeCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(Registry::class, function ($app) {
            return new Registry;
        });
    }
}
