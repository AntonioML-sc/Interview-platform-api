<?php

namespace App\Http\Controllers;

use App\Models\Application;
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
}
