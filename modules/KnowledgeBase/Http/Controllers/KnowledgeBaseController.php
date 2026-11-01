<?php

namespace Modules\KnowledgeBase\Http\Controllers;

use App\Http\Controllers\Controller;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        return view('knowledgebase::index');
    }
}
