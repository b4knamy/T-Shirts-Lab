<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserManagementController;
use Illuminate\Support\Facades\Route;

// Authenticated — own profile & addresses
Route::middleware('jwt.auth')->controller(UserController::class)->group(function () {
  Route::get('/users/me',                   'me');
  Route::patch('/users/me',                 'updateProfile');
  Route::post('/users/me/avatar',           'uploadAvatar');
  Route::get('/users/me/addresses',         'addresses');
  Route::post('/users/me/addresses',        'storeAddress');
  Route::patch('/users/me/addresses/{id}',  'updateAddress');
  Route::delete('/users/me/addresses/{id}', 'destroyAddress');
});

// Admin — staff management
Route::middleware(['jwt.auth', 'admin'])->controller(UserManagementController::class)->group(function () {
  Route::get('/users',        'index');
  Route::post('/users',       'store');
  Route::patch('/users/{id}', 'update');
});
