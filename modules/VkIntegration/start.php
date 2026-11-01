<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
*/

// Debug log
\Log::info('VkIntegration start.php loaded, routesAreCached: ' . (app()->routesAreCached() ? 'yes' : 'no'));

if (!app()->routesAreCached()) {
    \Log::info('VkIntegration loading routes from: ' . __DIR__ . '/Http/routes.php');
    require __DIR__ . '/Http/routes.php';
}
