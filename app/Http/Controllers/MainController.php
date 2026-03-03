<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Main;

class MainController extends Controller
{
    //
    public function index(){
        return view('main.dashboard');
    }
}
