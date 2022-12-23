<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            "email" => "email|required|unique",
            "password" => "required|string",
            "remember" => "boolean",
        ]);

        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        if (!Auth::attempt($credentials, $remember)) {
            return response()->json([
                "status" => 'error',
                "message" => 'Invalid credentials!',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        if (!$user->is_admin) {
            Auth::logout();
            return response()->json([
                "status" => 'error',
                "message" => 'You don\'t have permission!',
            ], Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken('main')->plainTextToken;
        return response()->json([
            "status" => 'success',
            "user" => new UserResource($user),
            "token" => $token
        ], Response::HTTP_OK);
    }

    public function logout()
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json([''], Response::HTTP_NO_CONTENT);
    }

    public function getUser(Request $request)
    {
        return new UserResource($request->user());
    }
}
