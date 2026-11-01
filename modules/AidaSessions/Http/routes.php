<?php

Route::group([
    'middleware' => ['web', 'auth', 'roles'],
    'roles'      => ['admin'],
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\AidaSessions\Http\Controllers',
], function () {
    Route::get('/ai-sessions', 'AidaSessionsController@index')->name('aidasessions.index');
    Route::get('/ai-sessions/{id}', 'AidaSessionsController@show')->name('aidasessions.show');
    Route::get('/ai-sessions/{id}/download', 'AidaSessionsController@download')->name('aidasessions.download');
    Route::post('/ai-sessions/{id}/delete', 'AidaSessionsController@delete')->name('aidasessions.delete');
});
