<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\facades\Hash;


use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);


        $user = User::create([
            'name'   => $validated['name'],
            'username'   => $validated['username'],
            'email'  => $validated['email'],
            'password' => bcrypt($validated['password'])
        ]);

        $token = $user->createToken('my-custom-key')->plainTextToken;

        $response = [
            'status' => true,
            'token'  => $token,
            'user'   => $user
        ];

        return response($response, 201);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'status' => false,
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'status' => true,
            'token' => $token,
            'user' => $user,
        ];

        return response($response, 201);
    }

    public function logout(Request $request) {
        auth()->user()->tokens()->delete();

        $response = [
            'status' => true,
            'message' => 'Logged out'
        ];

        return response($response, 200);
    }

}
