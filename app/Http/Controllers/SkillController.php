<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class SkillController extends Controller
{
    public function getAll()
    {
        // retrieve all of the registers in skills table
        try {

            Log::info("retrieving skills");

            $skills = Skill::all()->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skills retrieved successfully',
                    'data' => $skills
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving all skills" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving skills'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getByTitle($title)
    {
        // look for registers whose names include the string provided
        try {

            Log::info("Retrieving skills by title");

            $skills = Skill::query()
                ->where('title', 'like', '%' . $title . '%')
                ->orderBy('title', 'desc')
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skills retrieved successfully',
                    'data' => $skills
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving skills by title: " . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving skills by title'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function newSkill(Request $request)
    {
        // creates a new register in companies using info provided by request body. Recruiters only.
        try {

            Log::info('registering a new skill');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255|unique:skills',
                'description' => 'required|string|max:255'
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

            // create the new company with the values provided
            $skill = new Skill();

            $skill->title = $request->input('title');
            $skill->description = $request->input('description');

            $skill->save();

            // the creator of the skill is the first user and the admin of the skill
            $userId = auth()->user()->id;
            $skill->users()->attach($userId, ['creator' => true]);

            Log::info('New skill registered: ' . $skill->title);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'New skill added to database'
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {

            Log::error('Error adding new skill: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error adding new skill'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateSkill(Request $request, $skillId)
    {
        // update data of an existing skill
        try {

            Log::info('updating skill');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'title' => 'string|max:255|unique:skills',
                'description' => 'string|max:255'
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

            $skill = Skill::query()->find($skillId);

            // check if the skill really exists in db
            if (!$skill) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Invalid skill id'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // only the creator of the skill is allowed to edit it
            $userId = auth()->user()->id;
            $userIsCreator = SkillUser::query()
                ->where('skill_id', $skillId)
                ->where('user_id', $userId)
                ->first()
                ->creator;

            if (!$userIsCreator) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User not authorized'
                    ],
                    Response::HTTP_FORBIDDEN
                );
            }

            // edit the skill fields with the values provided
            $title = $request->input('title');
            $description = $request->input('description');

            if (isset($title)) $skill->title = $title;
            if (isset($description)) $skill->description = $description;

            $skill->save();

            Log::info('Skill data updated: ' . $skill->id);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Skill updated successfully',
                    'data' => $skill
                ]
            );
        } catch (\Exception $exception) {

            Log::error('Error updating skill: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating skill'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
