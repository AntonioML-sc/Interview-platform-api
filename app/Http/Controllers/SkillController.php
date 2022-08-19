<?php

namespace App\Http\Controllers;

use App\Models\Skill;
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
}
