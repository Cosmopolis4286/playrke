<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'root'])->name('root');
Route::get('getInfo/{page}', [PublicController::class, 'info'])->name('info');
