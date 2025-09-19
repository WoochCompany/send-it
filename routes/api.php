<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;

Route::post('/messages', [MessageController::class, 'store']);

