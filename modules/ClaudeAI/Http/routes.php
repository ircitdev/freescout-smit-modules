<?php

Route::group([
    'middleware' => ['web', 'auth', 'roles'],
    'roles'      => ['admin'],
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\ClaudeAI\Http\Controllers',
], function () {
    Route::get('/claudeai/settings', 'ClaudeAIController@settings')->name('claudeai.settings');
    Route::post('/claudeai/settings', 'ClaudeAIController@saveSettings')->name('claudeai.settings.save');
    Route::post('/claudeai/test', 'ClaudeAIController@testConnection')->name('claudeai.test');
    Route::post('/claudeai/operator-hint', 'ClaudeAIController@operatorHint')->name('claudeai.operator_hint');
    Route::post('/claudeai/auto-reply', 'ClaudeAIController@autoReply')->name('claudeai.auto_reply');
    Route::get('/claudeai/stats', 'ClaudeAIController@stats')->name('claudeai.stats');
    Route::get('/claudeai/docs', 'ClaudeAIController@docs')->name('claudeai.docs');
});
