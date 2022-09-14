<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillMark;
use App\Models\Test;
use App\Models\TestUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function getMyTests()
    {
        // get applications of logged user.
        try {
            Log::info("Retrieving user's tests");

            $userId = auth()->user()->id;

            $tests = Test::query()
                ->with('skills:id,title')
                ->with('users:id,last_name,first_name,email,title')
                ->join('test_user', 'tests.id', '=', 'test_user.test_id')
                ->select('tests.id as id', 'tests.date as date', 'tests.completed as completed', 'test_user.user_type as type')
                ->where('test_user.user_id', $userId)
                ->orderBy('tests.updated_at', 'desc')
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => "User's tests retrieved successfully",
                    'data' => $tests
                ]
            );
        } catch (\Exception $exception) {
            Log::error("Error retrieving user's tests" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error retrieving user's tests"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function newTest(Request $request)
    {
        // A recruiter posts a new test for a specific examinee. Skills and marks are added later.
        try {
            Log::info('Scheduling a new test');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'examinee_id' => 'required|uuid',
                'date' => 'required|string|date|max:255',
                'skills.*.id' => 'required|uuid'
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
            $skillArray = $request->input('skills');
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

            // check if all of the skills exist
            for ($i = 0; $i < count($skillArray); $i++) {
                $skill = Skill::find($skillArray[$i]['id']);
                if (!$skill) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'The skill specified is not in database'
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            // create the new test
            $test = new Test();
            $test->date = $date;
            $test->completed = false;
            $test->save();

            // attach users in pivot table test_user
            $test->users()->attach($recruiterId, ['user_type' => 'examiner']);
            $test->users()->attach($examineeId, ['user_type' => 'examinee']);

            // attach all of the skills to the test
            for ($i = 0; $i < count($skillArray); $i++) {
                $skill = Skill::find($skillArray[$i]['id']);
                $test->skills()->attach($skill->id, ['mark' => 0]);
            }

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
                'skill_id' => 'required|uuid',
                'test_id' => 'required|uuid'
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
                'skill_id' => 'required|uuid',
                'test_id' => 'required|uuid'
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

    public function evaluateSkill(Request $request, $skillMarkId)
    {
        // update field mark in skill_marks table. This settles one examinee skill mark in a specific test.
        try {
            Log::info('evaluating skill: updating mark in skill marks');

            // Validates mark
            $validator = Validator::make($request->all(), [
                'mark' => 'required|Integer|in:0,1,2,3,4,5,6,7,8,9,10'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
            }

            $skillMarkRegister = SkillMark::find($skillMarkId);
            $userId = auth()->user()->id;

            // check if the register exists in skill_marks table            
            if (!$skillMarkRegister) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Register not found'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the test examiner
            $examinerId = TestUser::query()
                ->where('test_id', $skillMarkRegister->test_id)
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

            // if everything is ok, update the mark
            $skillMarkRegister->mark = $request->input('mark');
            $skillMarkRegister->save();

            Log::info('Skill mark ' . $skillMarkRegister->id . ' edited');

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill mark registered'
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error evaluating skill: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error evaluating skill'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    
    public function evaluateTest(Request $request, $testId)
    {
        // set mark in different registers of skill_marks table related to the same test, given by $testId.
        // this is designed to evaluate all of the skills of a test
        try {
            Log::info('evaluating skill: updating mark in skill marks');

            // Validates mark
            $validator = Validator::make($request->all(), [
                'skills.*.id' => 'required|uuid',
                'skills.*.mark' => 'required|Integer|in:0,1,2,3,4,5,6,7,8,9,10'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
            }

            $test = Test::find($testId);
            $userId = auth()->user()->id;

            // check if the test exists in skill_marks table
            if (!$test) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Register not found'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the test examiner
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

            // check if all of the skills exist
            $skillArray = $request->input('skills');
            for ($i = 0; $i < count($skillArray); $i++) {
                $skill = Skill::find($skillArray[$i]['id']);
                if (!$skill) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'The skill specified is not in database'
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
            
            // if everything is ok, update the marks (the skills must be already in skill_marks)
            for ($i = 0; $i < count($skillArray); $i++) {
                $skillmark = SkillMark::query()->where('skill_id', $skillArray[$i]['id'])->first();
                if (isset($skillmark)) {
                    $skillmark->mark = $skillArray[$i]['mark'];
                    $skillmark->save();
                }
            }

            Log::info('Skill marks of test ' . $testId . ' edited');

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill marks registered'
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error evaluating test: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error evaluating test'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateTest(Request $request, $testId)
    {
        try {
            Log::info('Updating test');

            // Validate data
            $validator = Validator::make($request->all(), [
                'date' => 'String|date|max:255',
                'completed' => 'Boolean'
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

            $test = Test::find($testId);
            $userId = auth()->user()->id;

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

            // if everything is ok, update the test
            $date = $request->input('date');
            $completed = $request->input('completed');
            if (isset($date)) $test->date = $date;
            if (isset($completed)) $test->completed = $completed;
            $test->save();

            Log::info('Test ' . $testId . ' edited');

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Test edited'
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error updating test: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating test'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function deleteTest($testId)
    {
        try {
            Log::info('Deleting test');

            $test = Test::find($testId);
            $userId = auth()->user()->id;

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

            // if everything is ok, delete the test
            Log::info('Test ' . $testId . ' about to be deleted');
            $test->delete();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Test deleted'
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error deleting test: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error deleting test'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
