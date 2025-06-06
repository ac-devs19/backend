<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function user(Request $request)
    {
        $user = $request->user();

        $result = null;

        if ($user->role === "admin" || $user->role === "cashier") {
            $result = User::with('staff.information')
                ->find($user->id);
        } elseif ($user->role === "student") {
            $result = User::with('student.information')
                ->find($user->id);
        }

        return response()->json($result);
    }
}
