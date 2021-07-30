<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{App,
    Session
};
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\Language;

class HomeController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new Common();
    }

    public function index()
    {
        $data         = [];
        $data['menu'] = 'home';
        return view('frontend.home.index', $data);
    }

    public function setLocalization(Request $request)
    {
        $langShotCode = Language::where('status', 'active')->pluck('short_name')->toArray();

        if (!in_array($request->lang, $langShotCode))
        {
            return 0;
        }
        if (!$request->ajax())
        {
            return 0;
        }

        if ($request->lang)
        {
            App::setLocale($request->lang);
            Session::put('dflt_lang', $request->lang);
            return 1;
        }
        else
        {
            return 0;
        }
    }
}
