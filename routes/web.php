<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::post('/reset-password-code', [ResetPasswordController::class,'sendMail'])->middleware('guest')->name('password.email');
Route::post('/reset-password/{email}', [ResetPasswordController::class,'checkCode'])->middleware('guest')->name('password.check');
Route::post('/reset-password/{email}/update', [ResetPasswordController::class,'updatePassword'])->middleware('guest')->name('password.update');

Route::get('/test/cropper', function () {
    return Inertia::render('Test/CropProfilePhoto');
});
