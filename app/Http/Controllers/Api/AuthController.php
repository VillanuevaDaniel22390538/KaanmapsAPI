<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string', // Ej: 'android-app'
        ]);

        $user = User::where('Email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->Contraseña_Hash)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user->Estado !== 'Activo') {
             return response()->json(['message' => 'La cuenta está inactiva o suspendida.'], 403);
        }

        // Generar token de Sanctum
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'user' => [
                'id' => $user->ID_Usuario,
                'nombre' => $user->Nombre_Usuario,
                'email' => $user->Email,
                'tipo' => $user->Tipo_Usuario,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ], 200);
    }
}