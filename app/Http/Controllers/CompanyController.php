<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function getAll()
    {
        try {

            Log::info("retrieving companies");

            $companies = Company::all()->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Companies retrieved successfully',
                    'data' => $companies
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving all companies" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving games'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
