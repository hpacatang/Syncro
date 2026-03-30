<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Main;
use App\Models\Submission;

class MainController extends Controller
{
    //
    public function index(){
        return view('main.dashboard');
    }

    public function submissions() {
        $submissions = Submission::with('user')->orderBy('created_at', 'desc')->get();
        return view('Submission.SubmitForm', compact('submissions'));
    }

    public function notifications() {
        // You can pass the user's database notifications here
        $notifications = auth()->user()->notifications ?? [];
        return view('main.notifications', compact('notifications'));
    }
}
