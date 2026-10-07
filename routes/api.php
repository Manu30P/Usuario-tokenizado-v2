<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// 1. Obtener una lista de los 10 primeros usuarios
Route::get('/users', [UserController::class, 'getTenUsers']);

// 2. Crear usuario hasheando la contraseña
Route::post('/users', [UserController::class, 'create']);

// 3. Iniciar sesión generando y devolviendo un token de sesión
Route::post('/login', [UserController::class, 'login']);

// 4. Actualizar el campo name pasándole el token y el nuevo name
Route::put('/users/name', [UserController::class, 'updateName']);
Route::post('/users/update-name', [UserController::class, 'updateName']);
