<?php

namespace App\Http\Controllers;

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
}
