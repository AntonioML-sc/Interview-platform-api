<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // role ids from role seeder
    const ROLE_APPLICANT = "56d01e2e-2334-49c0-9469-4419d9cc0a62";
    const ROLE_RECRUITER = "5695fbbd-4675-4b2a-b31d-603252c21c94";

    public function register(Request $request)
    {

        try {

            Log::info('Trying to register a new user');

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|max:255|in:recruiter,applicant',
                'last_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|max:255',
                'phone' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'description' => 'string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
            }

            // role can only take the values 'recruiter' or 'applicant'
            $role = $request->get('role');

            // assign roleId to the chosen option. The default option is applicant. 
            switch ($role) {
                case 'recruiter':
                    $roleId = self::ROLE_RECRUITER;
                    break;

                default:
                    $roleId = self::ROLE_APPLICANT;
                    break;
            }

            $user = User::create([
                'role_id' => $roleId,
                'last_name' => $request->get('last_name'),
                'first_name' => $request->get('first_name'),
                'email' => $request->get('email'),
                'password' => bcrypt($request->password),
                'phone' => $request->get('phone'),
                'title' =>  $request->get('title'),
                'description' =>  $request->get('description'),
            ]);

            $token = JWTAuth::fromUser($user);

            Log::info('New user registered: ' . $user->email);

            return response()->json(compact('user', 'token'), Response::HTTP_CREATED);
        } catch (\Exception $exception) {

            Log::error("Error in registering new user: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error in registering new user'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function login(Request $request)
    {
        try {

            Log::info('User login');

            $input = $request->only('email', 'password');
            $jwt_token = null;

            if (!$jwt_token = JWTAuth::attempt($input)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Invalid Email or Password',
                    ],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            return response()->json([
                'success' => true,
                'token' => $jwt_token,
            ]);
        } catch (\Exception $exception) {

            Log::error("Error on login: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error on login'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
