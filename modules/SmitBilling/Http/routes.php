<?php

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'smitbilling'], function () {
    Route::post('/refresh/{conversationId}', 'Modules\SmitBilling\Http\Controllers\SmitBillingController@refresh');
    Route::post('/ping/{conversationId}',    'Modules\SmitBilling\Http\Controllers\SmitBillingController@ping');
    Route::post('/ai-assist',                'Modules\SmitBilling\Http\Controllers\SmitBillingController@aiAssist');
});
