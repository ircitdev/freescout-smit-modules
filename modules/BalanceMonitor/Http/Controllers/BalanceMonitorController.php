<?php
namespace Modules\BalanceMonitor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BalanceMonitorController extends Controller
{
    public function settings()
    {
        $settings = [
            "openai_balance" => \Option::get("balancemonitor.openai_balance", 0),
        ];
        return view("balancemonitor::settings", compact("settings"));
    }

    public function saveSettings(Request $request)
    {
        \Option::set("balancemonitor.openai_balance", (float)$request->input("openai_balance", 0));
        \Cache::forget("balance_monitor_data");
        \Session::flash("flash_success_floating", "Настройки сохранены");
        return redirect()->route("balancemonitor.settings");
    }
}
