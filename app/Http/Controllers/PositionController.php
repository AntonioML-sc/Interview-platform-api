<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Company;
use App\Models\Position;
use App\Models\Skill;
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

    public function attachSkill(Request $request)
    {
        // add a skill to the list of required skills of a position, stored in pivot table position_skill
        try {

            Log::info('Attaching a skill to the requirements of a position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'position_id' => 'required|string|max:36|min:36',
                'skill_id' => 'required|string|max:36|min:36'
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

            $position = Position::find($request->input('position_id'));
            $skill = Skill::find($request->input('skill_id'));

            // check if the position exists
            if (!$position) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The position specified is not in database'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the skill exists
            if (!$skill) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The skill specified is not in database'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the position admin
            $userId = auth()->user()->id;
            $positionAdminId = Application::query()
                ->where('position_id', $position->id)
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

            // if everything is ok, attach the skill to the position
            $positionHasSkill = $position->skills->contains($skill->id);
            if (!$positionHasSkill) {
                $position->skills()->attach($skill->id);
            }

            Log::info('Skill ' . $skill->title . ' added to requirements of position ' . $position->title);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill ' . $skill->title . ' added to requirements of position ' . $position->title
                ]
            );
        } catch (\Exception $exception) {

            Log::error('Error attaching skill to position: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error attaching skill to position'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function detachSkill(Request $request)
    {
        // remove a skill from the list of required skills of a position, stored in pivot table position_skill
        try {

            Log::info('Detaching a skill from the requirements of a position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'position_id' => 'required|string|max:36|min:36',
                'skill_id' => 'required|string|max:36|min:36'
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

            $position = Position::find($request->input('position_id'));
            $skill = Skill::find($request->input('skill_id'));

            // check if the position exists
            if (!$position) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The position specified is not in database'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the skill exists
            if (!$skill) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The skill specified is not in database'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // check if the logged user is the position admin
            $userId = auth()->user()->id;
            $positionAdminId = Application::query()
                ->where('position_id', $position->id)
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

            // if everything is ok, detach the skill from the position
            $position->skills()->detach($skill->id);

            Log::info('Skill ' . $skill->title . ' removed from requirements of position ' . $position->title);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill ' . $skill->title . ' removed from  requirements of position ' . $position->title
                ]
            );
        } catch (\Exception $exception) {

            Log::error('Error detaching skill from position: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error detaching skill from position'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
