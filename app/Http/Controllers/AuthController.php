<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillUser;
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
                'status' => 'active'
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

            $userStatus = auth()->user()->status;

            if ($userStatus == 'deleted') {
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
                'token' => $jwt_token
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

    public function myProfile()
    {
        $userId =  auth()->user()->id;
        $user = User::query()
            ->with(['skills', 'tests.skills', 'tests.users', 'positions.company', 'companies'])
            ->find($userId);

        Log::info('User ' . $user->email . ' has consulted their personal profile');
        return response()->json(
            [
                'success' => true,
                'message' => 'User profile retrieved',
                'data' => $user
            ]
        );
    }

    public function logout()
    {
        Log::info('Trying log out');

        try {

            JWTAuth::invalidate(auth());

            Log::info('Successful log out');

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (\Exception $exception) {

            Log::error("Error on logout: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Sorry, the user cannot be logged out'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateMyProfile(Request $request)
    {
        try {

            $user_id = auth()->user()->id;

            $user = User::query()->find($user_id);

            Log::info('User ' . $user_id . ": " . $user->email . ' updating their profile');

            $validator = Validator::make($request->all(), [
                'role' => 'string|max:255|in:recruiter,applicant',
                'last_name' => 'string|max:255',
                'first_name' => 'string|max:255',
                'email' => 'string|email|max:255',
                'password' => 'string|min:8|max:255',
                'phone' => 'string|max:255',
                'title' => 'string|max:255',
                'description' => 'string|max:255',
                'skills_to_attach.*.id' => 'uuid',
                'skills_to_detach.*.id' => 'uuid'
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $validator->errors()
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $role = $request->input("role");
            $lastName = $request->input("last_name");
            $firstName = $request->input("first_name");
            $email = $request->input("email");
            $password = $request->input("password");
            $phone = $request->input("phone");
            $title = $request->input("title");
            $description = $request->input("description");
            $skillsToAttach = $request->input("skills_to_attach");
            $skillsToDetach = $request->input("skills_to_detach");

            // verify the new email does not belong to any other user.
            // It does not throw an error if it is the same email that user previously had.
            if (isset($email)) {
                $user2 = User::query()->where('email', $email)->first();
                if (!$user2) {
                    $user->email = $email;
                } else if ($user2->id != $user->id) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'invalid email'
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
            
            if (isset($lastName)) {
                $user->last_name = $lastName;
            }

            if (isset($firstName)) {
                $user->first_name = $firstName;
            }

            if (isset($password)) {
                $user->password = bcrypt($password);
            }

            if (isset($phone)) {
                $user->phone = $phone;
            }

            if (isset($title)) {
                $user->title = $title;
            }

            if (isset($description)) {
                $user->description = $description;
            }

            if (isset($role)) {
                switch ($role) {
                    case 'recruiter':
                        $user->role_id = self::ROLE_RECRUITER;
                        break;

                    case 'applicant':
                        $user->role_id = self::ROLE_APPLICANT;
                        break;
                }
            }

            // function to check if all of the skills in an array exist
            function checkSkills($skillArray)
            {
                for ($i = 0; $i < count($skillArray); $i++) {
                    $skill = Skill::find($skillArray[$i]['id']);
                    if (!$skill) {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Any of the skills specified is not in database'
                            ],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                }
            }

            // if there are skills to attach, attach them if validation succeeds
            if (isset($skillsToAttach)) {
                checkSkills($skillsToAttach);
                for ($i = 0; $i < count($skillsToAttach); $i++) {
                    $userHasSkill = $user->skills->contains($skillsToAttach[$i]['id']);
                    if (!$userHasSkill) {
                        $user->skills()->attach($skillsToAttach[$i]['id'], ['creator' => false]);
                    }
                }
                Log::info('Skill list added to user known skills list');
            }

            // if there are skills to detach, do it if validation succeeds.
            // User can not detach their created skills
            if (isset($skillsToDetach)) {
                checkSkills($skillsToDetach);
                for ($i = 0; $i < count($skillsToDetach); $i++) {
                    $skillUser = SkillUser::query()
                        ->where('skill_id', $skillsToDetach[$i]['id'])
                        ->where('user_id', $user->id)
                        ->first();
                    if (isset($skillUser) && !$skillUser->creator) {
                        $user->skills()->detach($skillsToDetach[$i]['id']);
                    }
                }
                Log::info('Skill list removed from user known skills list');
            }

            $user->save();
            Log::info('User profile updated successfully. New data: ' . $user);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'User profile updated'
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {
            Log::error("Error updating profile: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating profile'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function deleteMyAccount()
    // logical deletion
    {
        try {

            $user_id = auth()->user()->id;

            $user = User::query()->find($user_id);

            Log::info('User ' . $user_id . ": " . $user->email . 'deleting their profile');

            $user->status = 'deleted';

            $user->save();

            JWTAuth::invalidate(auth());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'User profile deleted'
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error deleting profile: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error deleting profile'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
