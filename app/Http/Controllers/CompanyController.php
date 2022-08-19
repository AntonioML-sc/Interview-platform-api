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
                    'message' => 'Error retrieving companies'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getByName($name)
    {
        try {

            Log::info("Retrieving companies by name");

            $companies = Company::query()
                ->where('name', 'like', '%' . $name . '%')
                ->orderBy('name', 'asc')
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Companies retrieved successfully',
                    'data' => $companies
                ]
            );
        } catch (\Exception $exception) {

            Log::error("Error retrieving companies by name" . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving companies by name'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function newCompany(Request $request)
    {
        // creates a new register in companies using info provided by request body. Recruiters only.
        try {

            Log::info('registering a new company');

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:companies',
                'address' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:companies',
                'description' => 'required|string|max:255',
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

            $company = new Company();

            $company->name = $request->input('name');
            $company->address = $request->input('address');
            $company->email = $request->input('email');
            $company->description = $request->input('description');
            $company->status = 'active';

            $company->save();

            Log::info('New company registered: ' . $company->name);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'New company added'
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {

            Log::error('Error adding new company: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error adding new company'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateCompany(Request $request, $companyId)
    {
        // update data of an existing company. It can also act as a logical deletion by setting status column to deleted
        try {

            Log::info('updating company');

            // validate data provided by request body
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255|unique:companies',
                'address' => 'string|max:255',
                'email' => 'string|email|max:255|unique:companies',
                'description' => 'string|max:255',
                'status' => 'string|max:255|in:active,deleted'
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

            $company = Company::query()->find($companyId);

            // check if the company exists
            if (!$company) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Invalid company id'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // edit the company fields with the values provided

            $name = $request->input('name');
            $email = $request->input('email');
            $address = $request->input('address');
            $description = $request->input('description');
            $status = $request->input('status');
            
            if (isset($name)) {
                $company->name = $name;
            }

            if (isset($email)) {
                $company->email = $email;
            }
            
            if (isset($address)) {
                $company->address = $address;
            }
            
            if (isset($description)) {
                $company->description = $description;
            }
            
            if (isset($status)) {
                $company->status = $status;
            }

            $company->save();

            Log::info('Company data updated: ' . $company->id);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Company updated successfully',
                    'data' => $company
                ]
            );

        } catch (\Exception $exception) {

            Log::error('Error updating company: ' . $exception->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating company'
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
