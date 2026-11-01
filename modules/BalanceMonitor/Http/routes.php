<?php
Route::group(["middleware" => ["web", "auth", "roles"], "roles" => ["admin"], "prefix" => "balancemonitor", "namespace" => "Modules\BalanceMonitor\Http\Controllers"], function() {
    Route::get("/settings", "BalanceMonitorController@settings")->name("balancemonitor.settings");
    Route::post("/settings", "BalanceMonitorController@saveSettings")->name("balancemonitor.settings.save");
});
