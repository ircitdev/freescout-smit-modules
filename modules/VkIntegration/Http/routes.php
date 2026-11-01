<?php

// Webhook (no auth required)  
Route::group([
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\VkIntegration\Http\Controllers'
], function() {
    Route::post('/vk/webhook', 'VkController@webhook')->name('vkintegration.webhook');
    Route::get('/vk/config', 'VkController@getConfig')->name('vkintegration.config');
    Route::post('/vk/bridge-status', 'VkController@reportStatus')->name('vkintegration.bridge_status');
});

// Admin settings (auth required)
Route::group([
    'middleware' => 'web',
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\VkIntegration\Http\Controllers'
], function() {
    Route::match(['get', 'post'], '/vk/settings', ['uses' => 'VkController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('vkintegration.settings');
    Route::post('/vk/test-connection', ['uses' => 'VkController@testConnection', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('vkintegration.test_connection');
    Route::post('/vk/restart-bridge', ['uses' => 'VkController@restartBridge', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('vkintegration.restart_bridge');
    Route::post('/vk/stop-bridge', ['uses' => 'VkController@stopBridge', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('vkintegration.stop_bridge');
    Route::get('/vk/logs', ['uses' => 'VkController@viewLogs', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('vkintegration.logs');
});
