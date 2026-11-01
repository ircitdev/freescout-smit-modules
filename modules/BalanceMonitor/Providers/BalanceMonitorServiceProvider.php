<?php
namespace Modules\BalanceMonitor\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class BalanceMonitorServiceProvider extends ServiceProvider
{
    const CACHE_TTL = 300;

    public function boot()
    {
        $this->registerConfig();
        $this->loadRoutesFrom(__DIR__."/../Http/routes.php");
        $this->loadViewsFrom(__DIR__."/../Resources/views", "balancemonitor");
        \Eventy::addFilter("module.balancemonitor.settings_route", function($r) {
            return route("balancemonitor.settings");
        });
        $this->registerSettings();
        $this->hooks();
    }

    public function register() {}

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__."/../Config/config.php", "balancemonitor");
    }

    protected function registerSettings()
    {
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['balancemonitor'] = [
                'title' => 'Balance Monitor',
                'icon' => 'usd',
                'order' => 800
            ];
            return $sections;
        }, 30);

        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
            if ($section != 'balancemonitor') {
                return $settings;
            }
            return [
                'balancemonitor.openai_balance' => \Option::get('balancemonitor.openai_balance', 0)
            ];
        }, 20, 2);

        \Eventy::addFilter('settings.section_params', function($params, $section) {
            if ($section != 'balancemonitor') {
                return $params;
            }
            return [
                'template_vars' => [
                    'balances' => $this->getBalances()
                ]
            ];
        }, 20, 2);

        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section == 'balancemonitor') {
                return 'balancemonitor::settings_section';
            }
            return $view;
        }, 20, 2);
    }

    public function hooks()
    {
        $provider = $this;
        \Eventy::addAction("javascript", function() use ($provider) {
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) return;
            $b = $provider->getBalances();

            $sDanger = $b["serverspace"]["balance"] < 5;
            $oDanger = $b["openai"]["balance"] < 1;
            $mDanger = $b["mango"]["balance"] < 50;

            $sC = $sDanger ? "text-danger" : "";
            $sD = $b["serverspace"]["days"] ?? 0;
            $oC = $oDanger ? "text-danger" : "";
            $mC = $mDanger ? "text-danger" : "";

            $desktop = '<li class="balance-monitor bm-desktop" style="display:flex;align-items:center;gap:15px;padding:15px 10px;font-size:12px">';
            $desktop .= '<span class="'.$sC.'">&#128421; $'.number_format($b["serverspace"]["balance"],2).' ('.$sD.' д.)</span>';
            $desktop .= '<span class="'.$oC.'">&#129302; $'.number_format($b["openai"]["balance"],2).'</span>';
            $desktop .= '<span class="'.$mC.'">&#128222; '.number_format($b["mango"]["balance"],1).' р.</span>';
            $desktop .= '</li>';

            $mobile = '';
            if ($oDanger || $mDanger) {
                $mobile = '<div class="bm-mobile">';
                if ($oDanger) $mobile .= '<span>&#129302; $'.number_format($b["openai"]["balance"],2).'</span>';
                if ($mDanger) $mobile .= '<span>&#128222; '.number_format($b["mango"]["balance"],1).'</span>';
                $mobile .= '</div>';
            }

            $css = '.bm-desktop span.text-danger,.bm-mobile span{border-radius:11px;padding:5px 12px;margin:-5px 0;background:#fff;color:#a94442;}';
            $css .= '.bm-mobile{display:none;position:absolute;right:70px;top:16px;font-size:11px;gap:5px;}';
            $css .= '@media(max-width:767px){.bm-desktop{display:none!important;}.bm-mobile{display:flex!important;}}';

            echo 'var s=document.createElement("style");s.textContent="'.addslashes($css).'";document.head.appendChild(s);';
            echo '$(function(){$(".navbar-right .web-notifications").before("'.addslashes($desktop).'");';
            if ($mobile) echo '$(".navbar-header").append("'.addslashes($mobile).'");';
            echo '});';
        });
    }

    public function getBalances()
    {
        $p = $this;
        return Cache::remember("balance_monitor_data", self::CACHE_TTL, function() use ($p) {
            return [
                "serverspace" => $p->getServerSpaceBalance(),
                "openai" => $p->getOpenAIBalance(),
                "mango" => $p->getMangoBalance()
            ];
        });
    }

    public function getServerSpaceBalance()
    {
        try {
            $k = config("balancemonitor.serverspace.api_key");
            if (empty($k)) return ["balance" => 0, "days" => 0];

            $c = curl_init("https://api.serverspace.us/api/v1/project");
            curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["X-API-KEY: ".$k], CURLOPT_TIMEOUT => 10]);
            $r = curl_exec($c);
            curl_close($c);
            $d = json_decode($r, true);
            $bal = $d["project"]["balance"] ?? 0;

            $c = curl_init("https://api.serverspace.us/api/v1/servers");
            curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["X-API-KEY: ".$k], CURLOPT_TIMEOUT => 10]);
            $r = curl_exec($c);
            curl_close($c);
            $sd = json_decode($r, true);
            $m = 0;

            foreach (($sd["servers"] ?? []) as $s) {
                $price = [
                    "location_id" => $s["location_id"],
                    "image_id" => $s["image_id"],
                    "cpu" => $s["cpu"],
                    "ram_mb" => $s["ram_mb"],
                    "volumes" => array_map(function($v) { return ["name" => $v["name"], "size_mb" => $v["size_mb"]]; }, $s["volumes"] ?? []),
                    "nics" => array_map(function($n) { return ["network_type" => $n["network_type"], "bandwidth_mbps" => $n["bandwidth_mbps"]]; }, $s["nics"] ?? [])
                ];
                $c = curl_init("https://api.serverspace.us/api/v1/servers/price");
                curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($price), CURLOPT_HTTPHEADER => ["X-API-KEY: ".$k, "Content-Type: application/json"], CURLOPT_TIMEOUT => 10]);
                $r = curl_exec($c);
                curl_close($c);
                $pr = json_decode($r, true);
                $m += $pr["price"] ?? 0;
            }

            $dc = $m / 30;
            $dys = $dc > 0 ? floor($bal / $dc) : 0;
            return ["balance" => $bal, "days" => $dys];
        } catch (\Exception $e) {
            \Log::error("BM SS: ".$e->getMessage());
            return ["balance" => 0, "days" => 0];
        }
    }

    public function getOpenAIBalance()
    {
        return ["balance" => (float)\Option::get("balancemonitor.openai_balance", 0)];
    }

    public function getMangoBalance()
    {
        try {
            $k = config("balancemonitor.mango.api_key");
            $sk = config("balancemonitor.mango.secret_key");
            if (empty($k) || empty($sk)) return ["balance" => 0];

            $j = "{}";
            $sg = hash("sha256", $k.$j.$sk);
            $c = curl_init("https://app.mango-office.ru/vpbx/account/balance");
            curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(["vpbx_api_key" => $k, "sign" => $sg, "json" => $j]), CURLOPT_TIMEOUT => 10]);
            $r = curl_exec($c);
            curl_close($c);
            $d = json_decode($r, true);
            return ["balance" => $d["balance"] ?? 0];
        } catch (\Exception $e) {
            \Log::error("BM Mango: ".$e->getMessage());
            return ["balance" => 0];
        }
    }
}
