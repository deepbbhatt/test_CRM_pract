<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomFieldDefinationController;
use App\Http\Controllers\MergeController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('login', [AuthController::class, 'index'])->name('login');
Route::post('post-login', [AuthController::class, 'postLogin'])->name('login.post'); 
Route::get('registration', [AuthController::class, 'registration'])->name('register');
Route::post('post-registration', [AuthController::class, 'postRegistration'])->name('register.post'); 
Route::get('dashboard', [AuthController::class, 'dashboard']); 
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/contacts', [ContactController::class,'index'])->name('contacts.index');
Route::get('/contacts/create', [ContactController::class,'create'])->name('contacts.create');

Route::post('/contacts/ajax-list', [ContactController::class,'ajaxList'])->name('contacts.ajaxList');
Route::post('/contacts', [ContactController::class,'store'])->name('contacts.store');
Route::get('/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
// Route::post('/contacts/{contact}', [ContactController::class,'update'])->name('contacts.update');
Route::put('/contacts/{contact}', [ContactController::class,'update'])->name('contacts.update');

Route::delete('/contacts/{contact}', [ContactController::class,'destroy'])->name('contacts.destroy');

// Custom fields admin
Route::resource('custom-fields', CustomFieldDefinationController::class)->parameters(['custom-fields' => 'custom_field'])->names([
    'index' => 'custom-fields.index',
    'create' => 'custom-fields.create',
    'store' => 'custom-fields.store',
    'edit' => 'custom-fields.edit',
    'update' => 'custom-fields.update',
    'destroy' => 'custom-fields.destroy',
]);
Route::get('/contacts/list-json', [ContactController::class, 'listJson'])->name('contacts.listJson');

Route::post('/contacts/merge', [ContactController::class, 'merge'])->name('contacts.merge');


