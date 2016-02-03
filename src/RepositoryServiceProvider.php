<?php
namespace Pion\Repository;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    const NAME = "repository-controllers";

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        
    }

    public function boot()
    {

        $this->loadTranslationsFrom(__DIR__.'/resources', self::NAME);

        $this->publishes([
            __DIR__.'/resources' => resource_path('lang/vendor/'.self::NAME),
        ]);
    }

}