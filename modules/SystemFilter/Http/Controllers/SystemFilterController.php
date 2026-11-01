<?php

namespace Modules\SystemFilter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SystemFilterController extends Controller
{
    /**
     * Страница настроек и документации модуля SystemFilter
     */
    public function settings()
    {
        // Только для админов
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            abort(403);
        }
        
        return view('systemfilter::settings');
    }
}
