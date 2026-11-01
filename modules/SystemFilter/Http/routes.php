<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'prefix'     => Helper::getSubdirectory(),
    'namespace'  => 'Modules\SystemFilter\Http\Controllers'
], function() {
    Route::get('/systemfilter/settings', 'SystemFilterController@settings')->name('systemfilter.settings');
});
