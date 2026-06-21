<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{

    //the register function
    public function register ( Request $request ) {

    // let do the validation
    $request->validate([
        'name'=>'required|string',
        'email'=>'required|email|unique:users',
        'password'=>'required|min:6',
        'role'=>'required|in:owner,client'
    ]);
    // let create the user in database
    $user = User::create([
        'name'=>$request->name,
        'email'=>$request->email,
        'password'=>Hash::make($request->password),
        'role'=>$request->role
    ]);
    // let return the response
    return response()->json([
        'message'=>'User registered successfully',
        'user'=>$user
    ], 201);

    }

    // the login function
    public function login ( Request $request ) {

    $request->validate([
        'email'=>'required|email',
        'password'=>'required|string',
    ]);

    // let access the created user from the database
    $user = User::where('email', $request->email)->first();
    // let check the user if is authenticated
    if ( !$user || !Hash::check($request->password, $user->password) ) {
        // return invalid credentials result
        return response()->json([
            'error'=>'Invalid credentials'
        ], 401);
    }
    // let create a token for authenticated user
    $token = $user->createToken('auth_token')->plainTextToken;
    // let returnt eh final result if authenticated
    return response()->json([
        'success'=>true,
        'message'=>"Login successful",
        'token'=>$token,
        'user'=>$user
    ]);

    }

}
