<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Company;
use App\Models\Position;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                ->with('skills:id,title,description')
                ->with('company:id,name,address,email,description')
                ->with('users:id,email,role_id')
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

    public function getById($positionId)
    {
        // retrieve the position with id given if it is active and its required skills
        try {
            Log::info("retrieving position by id");

            $position = Position::query()
                ->with('skills:id,title,description')
                ->with('company:id,name,address,email,description')
                ->with('users:id,email,role_id')
                ->where('open', true)
                ->find($positionId);

            // error message if the position does not exist
            if (!$position) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The position specified is not in database'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Position retrieved successfully',
                    'data' => $position
                ]
            );
        } catch (\Exception $exception) {
            Log::error("Error retrieving position by id" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving position by id'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getByKeyWords($word)
    {
        // look for registers whose names include the string provided
        try {
            Log::info("Retrieving positions by title");

            $positions = Position::query()
                ->leftJoin('position_skill', 'positions.id', '=', 'position_skill.position_id')
                ->leftJoin('skills', 'skills.id', '=', 'position_skill.skill_id')
                ->join('companies', 'positions.company_id', '=', 'companies.id')
                ->select('positions.id as id', 'positions.title as title', 'positions.company_id as company_id', 'positions.location as location', 'positions.mode as mode', 'positions.salary as salary', 'positions.description as description', 'positions.created_at as release_date')
                ->with('skills:id,title,description')
                ->with('company:id,name,address,email,description')
                ->with('users:id,email,role_id')
                ->where('open', true)
                ->where(function ($query) use ($word) {
                    $query
                        ->where(DB::raw('LOWER(positions.title)'), 'like', '%' . strtolower($word) . '%')
                        ->orWhere(DB::raw('LOWER(positions.description)'), 'like', '%' . strtolower($word) . '%')
                        ->orWhere(DB::raw('LOWER(positions.location)'), 'like', '%' . strtolower($word) . '%')
                        ->orWhere(DB::raw('LOWER(companies.name)'), 'like', '%' . strtolower($word) . '%')
                        ->orWhere(DB::raw('LOWER(skills.title)'), 'like', '%' . strtolower($word) . '%');
                })
                ->groupBy('positions.id', 'positions.title', 'positions.company_id', 'positions.location', 'positions.mode', 'positions.salary', 'positions.description', 'positions.created_at')
                ->orderBy('positions.created_at', 'desc')
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
            Log::error("Error retrieving positions by title: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving positions by title'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getByCompanyId($companyId)
    {
        // retrieve the position with company id given and its required skills if it is active
        try {
            Log::info("retrieving positions by company id");

            $position = Position::query()
                ->with('skills:id,title,description')
                ->with('company:id,name,address,email,description')
                ->with('users:id,email,role_id')
                ->where('open', true)
                ->where('company_id', $companyId)
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Position retrieved successfully',
                    'data' => $position
                ]
            );
        } catch (\Exception $exception) {
            Log::error("Error retrieving position by id" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving position by id'
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
                'location' => 'required|string|max:255',
                'mode' => 'required|string|max:255',
                'salary' => 'required|string|max:255',
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

            // check if the company exists and if logged user is the company admin
            $user = auth()->user();
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
            if ($user->id != $company->user_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not allowed to this operation'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // create the new position with the values provided
            $position = new Position();
            $position->title = $request->input('title');
            $position->description = $request->input('description');
            $position->location = $request->input('location');
            $position->mode = $request->input('mode');
            $position->salary = $request->input('salary');
            $position->company_id = $company->id;
            $position->open = true;
            $position->save();

            // register the position's creator as the admin by adding a register in applications
            $position->users()->attach($user->id, ['status' => 'admin']);

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

    public function attachSkillArray(Request $request)
    {
        // add a list of skills (provided in a skills array in $request) to the list of required skills
        // of a position, stored in pivot table position_skill
        try {
            Log::info('Attaching a list of skills to the requirements of a position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'position_id' => 'required|string|max:36|min:36',
                'skills.*.id' => 'required|string|max:36|min:36'
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

            // check if the position exists
            $position = Position::find($request->input('position_id'));
            if (!$position) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The position specified is not in database'
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

            // if everything is ok, attach all of the skills to the position
            for ($i = 0; $i < count($skillArray); $i++) {
                $skill = Skill::find($skillArray[$i]['id']);
                $positionHasSkill = $position->skills->contains($skill->id);
                if (!$positionHasSkill) {
                    $position->skills()->attach($skill->id);
                }
            }
            Log::info('Skill list added to requirements of position ' . $position->title);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill list added to requirements of position ' . $position->title
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error attaching skills to position: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error attaching skills to position'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function detachSkillArray(Request $request)
    {
        // remove a list of skills (provided in a skills array in $request) from the list of required skills
        // of a position, stored in pivot table position_skill
        try {
            Log::info('Detaching a list of skills to the requirements of a position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'position_id' => 'required|string|max:36|min:36',
                'skills.*.id' => 'required|string|max:36|min:36'
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

            // check if the position exists
            $position = Position::find($request->input('position_id'));
            if (!$position) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The position specified is not in database'
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

            // if everything is ok, detach all of the skills from the position_skill pivot table
            for ($i = 0; $i < count($skillArray); $i++) {
                $skill = Skill::find($skillArray[$i]['id']);
                $position->skills()->detach($skill->id);
            }
            Log::info('Skill list removed from requirements of position ' . $position->title);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill list removed from requirements of position ' . $position->title
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error detaching skills to position: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error detaching skills to position'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updatePosition(Request $request, $positionId)
    {
        // update register in positions table using info provided by request body. Recruiters only.
        // it also works as a logical deletion by setting the field 'open' to 'false'.
        try {
            Log::info('updating position');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'title' => 'string|max:255|unique:positions',
                'description' => 'string|max:255',
                'company_name' => 'string|max:255',
                'location' => 'required|string|max:255',
                'mode' => 'required|string|max:255',
                'salary' => 'required|string|max:255',
                'open' => 'boolean'
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

            $position = Position::find($positionId);

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
                        'message' => 'User not allowed to do this operation'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // edit the position with the values provided
            $title = $request->input('title');
            $description = $request->input('description');
            $companyName = $request->input('company_name');
            $location = $request->input('location');
            $mode = $request->input('mode');
            $salary = $request->input('salary');
            $open = $request->input('open');

            if (isset($companyName)) {
                // check if the new company exists and if logged user is the company admin
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
                if ($userId != $company->user_id) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'User not allowed to this operation'
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                $position->company_id = $company->id;
            }

            if (isset($title)) $position->title = $title;
            if (isset($location)) $position->location = $location;
            if (isset($mode)) $position->mode = $mode;
            if (isset($salary)) $position->salary = $salary;
            if (isset($description)) $position->description = $description;
            if (isset($open)) $position->open = $open;
            $position->save();

            Log::info('Position edited: ' . $position->id . ': ' . $position->title);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Position edited: ' . $position->title
                ]
            );
        } catch (\Exception $exception) {
            Log::error('Error editing position: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error editing position'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
