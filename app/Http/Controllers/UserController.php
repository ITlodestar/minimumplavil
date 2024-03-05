<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'user_type' => 'required',
            'user_tgid' => 'required|unique:users,tgid',
            'user_nickname' => 'required',
        ]);

        // Create a new user
        $user = User::create([
            'type' => $validatedData['user_type'],
            'tgid' => $validatedData['user_tgid'],
            'nickname' => $validatedData['user_nickname'],
        ]);

        // Return a response with the newly created user
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

public function getUserByTgid(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'user_tgid' => 'required',
    ]);

    // Retrieve the user by tgid
    $user = User::where('tgid', $validatedData['user_tgid'])->first();

    // Return a response with the user information
    if ($user) {
        return response()->json([
            'message' => 'User found',
            'user' => $user,
        ]);
    } else {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }
}
}
