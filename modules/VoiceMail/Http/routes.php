<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'prefix'     => Helper::getSubdirectory(),
    'namespace'  => 'Modules\VoiceMail\Http\Controllers'
], function() {
    Route::get('/voicemail/settings', 'VoiceMailController@settings')->name('voicemail.settings');
});
