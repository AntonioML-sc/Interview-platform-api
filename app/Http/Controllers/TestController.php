<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\Test;
use App\Models\TestUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function newTest(Request $request)
    {
        // A recruiter posts a new test for a specific examinee. Skills and marks are added later.
        try {
            Log::info('Scheduling a new test');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'examinee_id' => 'required|string|max:36|min:36',
                'date' => 'required|string|date|max:255'
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

            $examineeId = $request->input('examinee_id');
            $date = $request->input('date');
            $recruiterId = auth()->user()->id;

            // check if the examinee id is correct
            $examinee = User::find($examineeId);

            if (!$examinee) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not found'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // create the new test
            $test = new Test();
            $test->date = $date;
            $test->completed = false;
            $test->save();

            // attach users in pivot table test_user
            $test->users()->attach($recruiterId, ['user_type' => 'examiner']);
            $test->users()->attach($examineeId, ['user_type' => 'examinee']);

            Log::info('Recruiter ' . $recruiterId . ' has scheduled a test for employee ' . $examineeId);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Test scheduled'
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {
            Log::error("Error scheduling a new test" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error scheduling a new test"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function attachSkill(Request $request)
    {
        try {
            Log::info('adding a skill to test');

            // Validates skill_id
            $validator = Validator::make($request->all(), [
                'skill_id' => 'required|string|max:36|min:36',
                'test_id' => 'required|string|max:36|min:36'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
            }

            $skillId = $request->input('skill_id');
            $skill = Skill::find($skillId);

            // check if the skill exists
            if (!$skill) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The skill specified does not exist'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $testId = $request->input('test_id');
            $test = Test::find($testId);

            // check if the test exists
            if (!$test) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The test specified does not exist'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the test examiner
            $userId = auth()->user()->id;
            $examinerId = TestUser::query()
                ->where('test_id', $testId)
                ->where('user_type', 'examiner')
                ->first()
                ->user_id;

            if ($userId != $examinerId) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not allowed to this operation'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // If everything is ok, attach the skill
            $testHasSkill = $test->skills->contains($skillId);
            if (!$testHasSkill) {
                $test->skills()->attach($skillId, ['mark' => 0]);
            }

            Log::info('The skill ' . $skill->title . ' has been added to test');

            return response()->json(
                [
                    'success' => true,
                    'message' => 'The skill ' . $skill->title . ' has been added to test'
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $exception) {
            Log::error('Error adding skill to test: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error adding skill to test'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function detachSkill(Request $request)
    {
        try {
            Log::info('adding a skill to test');

            // Validates skill_id
            $validator = Validator::make($request->all(), [
                'skill_id' => 'required|string|max:36|min:36',
                'test_id' => 'required|string|max:36|min:36'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
            }

            $skillId = $request->input('skill_id');
            $skill = Skill::find($skillId);

            // check if the skill exists
            if (!$skill) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The skill specified does not exist'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $testId = $request->input('test_id');
            $test = Test::find($testId);

            // check if the test exists
            if (!$test) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The test specified does not exist'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the test examiner
            $userId = auth()->user()->id;
            $examinerId = TestUser::query()
                ->where('test_id', $testId)
                ->where('user_type', 'examiner')
                ->first()
                ->user_id;

            if ($userId != $examinerId) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not allowed to this operation'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // If everything is ok, detach the skill            
            $test->skills()->detach($skillId);

            Log::info('The skill ' . $skill->title . ' has been removed from test');

            return response()->json(
                [
                    'success' => true,
                    'message' => 'The skill ' . $skill->title . ' has been removed from test'
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $exception) {
            Log::error('Error removing skill from test: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error removing skill from test'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
