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
        $resources = __DIR__.'/../resources/lang';

        $this->loadTranslationsFrom($resources, self::NAME);

        $this->publishes([
            $resources => \resource_path('lang/vendor/'.self::NAME),
        ]);
    }

}