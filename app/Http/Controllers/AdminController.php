<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        return response()->json([
            'users_count' => User::count(),
            'owners_count' => User::where('role', 'owner')->count(),
            'clients_count' => User::where('role', 'client')->count(),
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
