<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Obtener una lista de los 10 primeros usuarios.
     */
    public function getTenUsers()
    {
        try {
            $users = User::take(10)->get();

            return response()->json([
                'success' => true,
                'message' => '10 primeros usuarios obtenidos correctamente',
                'data' => $users,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Crear usuario hasheando la contraseña.
     */
    public function create(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $user,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Iniciar sesión generando y devolviendo un token de sesión.
     */
    public function login(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
            ]);

            // Verificar si el usuario existe
            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas',
                    'data' => null,
                ], 401);
            }

            // Eliminar token anterior si existe
            Token::where('user_id', $user->id)->delete();

            // Generar nuevo token
            $token = hash('sha256', Str::random(64));

            Token::create([
                'user_id' => $user->id,
                'token' => $token,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                ],
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Actualizar el campo name pasándole el token y el nuevo name.
     */
    public function updateName(Request $request)
    {
        try {
            $data = $request->validate([
                'token' => 'required|string',
                'name' => 'required|string|max:255',
            ]);

            $tokenRecord = Token::where('token', $data['token'])->first();

            if (! $tokenRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no válido',
                    'data' => null,
                ], 401);
            }

            $user = User::find($tokenRecord->user_id);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'data' => null,
                ], 404);
            }

            $user->name = $data['name'];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Nombre actualizado correctamente',
                'data' => $user,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 400);
        }
    }
}
