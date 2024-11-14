<?php

namespace App\Controllers;

class Users extends BaseController
{
    public function indexnonlogin(): string
    {
        return view('users/indexnonlogin');
    }

    public function aboutusnonlogin(): string
    {
        return view('users/aboutusnonlogin');
    }

    public function index(): string
    {
        return view('users/index');
    }

    public function listPackage()
    {
        return view('users/listpackage');
    }

    public function detailPackage()
    {
        return view('users/detailpackage');
    }

    public function aboutUs()
    {
        return view('users/aboutus');
    }
}
