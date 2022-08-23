<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function getAll()
    {
        try {
            Log::info('Retrieving all users');

            $users = User::query()
                ->with('skills:id,title,description')
                ->select('id', 'last_name', 'first_name', 'title', 'email', 'phone', 'description')
                ->whereNot('status', 'deleted')
                ->orderBy('last_name', 'desc')
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => "Users retrieved successfully",
                    'data' => $users
                ]
            );
        } catch (\Exception $exception) {
            Log::error("Error retrieving users" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error retrieving users"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
