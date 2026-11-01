<?php

Route::group(['middleware' => 'web', 'prefix' => '', 'namespace' => 'Modules\KnowledgeBase\Http\Controllers'], function () {
    Route::get('/knowledgebase', ['uses' => 'KnowledgeBaseController@index', 'as' => 'knowledgebase.index'])->middleware('auth');
});
