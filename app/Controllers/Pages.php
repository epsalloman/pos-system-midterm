<?php

namespace App\Controllers;

class Pages extends BaseController
{
    public function home()
    {
        return view('home');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function about()
    {
        return view('about');
    }
}
