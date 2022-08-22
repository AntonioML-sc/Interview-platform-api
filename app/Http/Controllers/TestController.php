<?php

namespace App\Http\Controllers;

use App\Models\Test;
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
}
