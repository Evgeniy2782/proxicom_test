<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerGenerateConfig;
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
    return view('welcome', ['config' => $config = false]);
})->name('home');

Route::get('/BuildConfig', [ControllerGenerateConfig::class, 'generateConfig'])->name('generate-config');

Route::get('/download', [ControllerGenerateConfig::class, 'download'])->name('download-config');
/*
Route::get('/buildconfig', function () {
    $id = request('id');
    return view('welcome', [
        'id' => $id
    ]);
});
*/

// Route::get('/user', [UserController::class, 'index']);
