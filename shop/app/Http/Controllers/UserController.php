<?php

namespace App\Http\Controllers;

use App\Models\User;

use App\Http\Controllers\Controller;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        return new UserResource($request->user());
    }

    //Метод для получения всех профилей пользователей (только для администратора)
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return UserResource::collection(User::all());
    }
}