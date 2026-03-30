<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MainController;

Route::get('/', function () {
    return redirect('login');
});

Route::get('/login', [UserController::class, 'login'])->name('login');
Route::post('authenticate', [UserController::class, 'authenticate'])->name('authenticate');
Route::get('/register', [UserController::class, 'register'])->name('register');
Route::post('store', [UserController::class, 'store'])->name('store');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function(){
    Route::get('/dashboard', [MainController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/submissions', [MainController::class, 'submissions'])->name('dashboard.submissions');
    Route::get('/dashboard/notifications', [MainController::class, 'notifications'])->name('dashboard.notifications');
});