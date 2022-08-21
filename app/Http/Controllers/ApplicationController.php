<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ApplicationController extends Controller
{
    public function getByPositionId($positionId)
    {
        // get applications by position id. Only available for the position admin.
        try {

            Log::info('Retrieving applications by position id');

            // check if the logged user is the position admin
            $userId = auth()->user()->id;
            $positionAdminId = Application::query()
                ->where('position_id', $positionId)
                ->where('status', 'admin')
                ->first()
                ->user_id;

            if ($userId != $positionAdminId) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not allowed to this operation'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $applications = Application::query()
                ->where('position_id', $positionId)
                ->orderBy('status', 'desc')
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Applications retrieved successfully',
                    'data' => $applications
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving applications by position id" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error retrieving applications by position id"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getMyApplications()
    {
        // get applications of logged user.
        try {

            Log::info("Retrieving user's applications");

            $userId = auth()->user()->id;

            $applications = Application::query()
                ->where('user_id', $userId)
                ->orderBy('status', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => "User's Applications retrieved successfully",
                    'data' => $applications
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving user's applications" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error retrieving user's applications"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function applyForPosition(Request $request)
    {
        try {

            Log::info('User applying for position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'position_id' => 'required|string|max:36|min:36'
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

            $user = auth()->user();
            $positionId = $request->input('position_id');

            // check if the position exists and it is still open
            $position = Position::query()
                ->where('open', true)
                ->find($positionId);

            if (!$position) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Position not available'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if user already applied for this position
            $application = Application::query()
                ->where('position_id', $positionId)
                ->where('user_id', $user->id)
                ->first();

            if ($application) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The register already exists'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // register the new application
            $application = new Application();
            $application->position_id = $positionId;
            $application->user_id = $user->id;
            $application->status = 'pending';
            $application->save();

            Log::info('User ' . $user->id . 'has applied for position ' . $positionId);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'User ' . $user->email . ' has applied for position ' . $position->title
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {

            Log::error("Error applying for position" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error applying for position"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function rejectApplication($applicationId)
    {
        // Set an application as rejected
        try {

            Log::info('Rejecting applicant');

            $application = Application::find($applicationId);

            // check if the application exists
            if (!$application) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The application is not registered'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the position admin
            $userId = auth()->user()->id;
            $positionAdminId = Application::query()
                ->where('position_id', $application->position_id)
                ->where('status', 'admin')
                ->first()
                ->user_id;

            if ($userId != $positionAdminId) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not allowed to this operation'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // reject the applicant
            $application->status = 'rejected';
            $application->save();

            Log::info('Application ' . $applicationId . ' has been discarded by position admin');
            
            return response()->json(
                [
                    'success' => true,
                    'message' => 'applicant rejected'
                ]
            );

        } catch (\Exception $exception) {

            Log::error("Error rejecting applicant" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => "Error rejecting applicant"
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
