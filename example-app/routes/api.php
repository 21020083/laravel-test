<?php

use App\Http\Controllers\api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'create'])->name('create');
        Route::get('/show', [UserController::class, 'detail'])->name('detail');
        Route::put('/update', [UserController::class, 'update'])->name('update');
        Route::delete('/delete', [UserController::class, 'delete'])->name('delete');
    });
});
