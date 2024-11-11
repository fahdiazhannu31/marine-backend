<?php

namespace App\Controllers;

class Home extends BaseController
{

    public function __construct()
    {
        helper('form');
    }
    
    public function index(): string
    {
        return view('users/index');
    }

    public function register()
    {
        return view('auth/register');
    }
}
