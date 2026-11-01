<?php

// Endpoint для моста ВКонтакте: без сессии и CSRF, защищён общим секретом (заголовок X-Bridge-Secret)
Route::group([
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\SatRatingsChat\Http\Controllers',
], function () {
    Route::post('/satratingschat/vk-rate', 'SatRatingsChatController@vkRate');
});
