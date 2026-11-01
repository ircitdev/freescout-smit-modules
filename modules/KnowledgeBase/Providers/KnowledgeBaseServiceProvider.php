<?php

namespace Modules\KnowledgeBase\Providers;

use Illuminate\Support\ServiceProvider;

class KnowledgeBaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerViews();
        $this->registerRoutes();
    }

    public function register()
    {
        //
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'knowledgebase');
    }

    protected function registerRoutes()
    {
        $routesPath = __DIR__ . '/../Http/routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }
}
