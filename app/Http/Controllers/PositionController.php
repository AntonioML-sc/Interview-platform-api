<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Company;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PositionController extends Controller
{
    public function getAll()
    {
        // retrieve all of the active (not deleted) positions
        try {

            Log::info("retrieving all positions");

            $positions = Position::query()
                ->where('open', true)
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Positions retrieved successfully',
                    'data' => $positions
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving all positions" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving positions'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function newPosition(Request $request)
    {
        // creates a new register in positions table using info provided by request body. Recruiters only.
        try {

            Log::info('registering a new position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255|unique:positions',
                'description' => 'required|string|max:255',
                'company_name' => 'required|string|max:255'
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

            // check if the company exists
            $company = Company::query()
                ->where('name', $request->input('company_name'))
                ->first();

            if (!$company) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The company specified is not in database'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // create the new position with the values provided
            $position = new Position();
            $position->title = $request->input('title');
            $position->description = $request->input('description');
            $position->company_id = $company->id;
            $position->open = true;
            $position->save();

            // register the position's creator as the admin by adding a register in applications
            $user = auth()->user();
            $application = new Application();
            $application->position_id = $position->id;
            $application->user_id = $user->id;
            $application->status = 'admin';
            $application->save();

            Log::info('New position registered: ' . $position->title . ' in company ' . $company->name . ' by user ' . $user->email);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'New position added'
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {

            Log::error('Error adding new position: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error adding new position'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
