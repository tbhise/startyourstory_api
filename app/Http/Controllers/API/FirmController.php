<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FirmController extends Controller
{
    public function registerFirm(Request $request)
    {
        DB::beginTransaction();
        try {
            // Log::info($request->all());


            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required|unique:users,mobile',
                'password' => 'required|min:6|max:10',
                'firmName' => 'required',
                'city' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()->first()
                ]);
            }




            // create user
            $userId = DB::table('users')->insertGetId([
                'name' => $request->firmName,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => bcrypt($request->password),
                'role' => 'firm',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // create profile
            DB::table('firm_profiles')->insert([
                'user_id' => $userId,
                'firm_name' => $request->firmName,
                'city' => $request->city,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Firm Registration successfull..!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Firm Registration Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Firm Registration failed: Server error'
            ]);
        }
    }




    public function firm_profile_update(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->attributes->get('auth_user');
            $userId = $user->id;


            /*
        |--------------------------------------------------------------------------
        | Check Existing Firm Profile
        |--------------------------------------------------------------------------
        */

            $firmProfile = DB::table('firm_profiles')
                ->where('user_id', $userId)
                ->first();

            /*
        |--------------------------------------------------------------------------
        | Prepare Data
        |--------------------------------------------------------------------------
        */

            $updateData = [
                'user_id' => $userId,
                'firm_name' => $request->firm_name,
                'frn' => $request->frn,
                'hr_name' => $request->hr_name,

                'firm_type' => $request->firm_type,
                'about' => $request->about,
                'establishment_year' => $request->establishment_year,
                'services_offered' => $request->services_offered,
                'industries_served' => $request->industries_served,

                'partners_count' => $request->partners,
                'employees_count' => $request->employees,
                'articles_count' => $request->articles,

                'exposure_type' => $request->exposure_type,

                'linkedin_url' => $request->linkedin_url,
                'website_url' => $request->website_url,
                'instagram_url' => $request->instagram_url,
                'facebook_url' => $request->facebook_url,
                'other_links' => $request->other_links,

                'work_modes' => $request->work_modes,
                'training_details' => $request->training_details,
                'stipend_details' => $request->stipend_details,
                'additional_contacts' => $request->additional_contacts,

                'updated_at' => now(),
            ];


            $userUpdateData = [
                'name' => $request->firm_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'profile_completed' => 1,
                'updated_at' => now(),
            ];

            /*
        |--------------------------------------------------------------------------
        | Logo Upload
        |--------------------------------------------------------------------------
        */

            if ($request->hasFile('logo')) {

                $logo = $request->file('logo');

                $logoName = time() . '_logo.' .
                    $logo->getClientOriginalExtension();

                $logoPath = $logo->storeAs(
                    'firm/logo',
                    $logoName,
                    'public'
                );

                $updateData['logo_path'] = $logoPath;
                $userUpdateData['profile_image'] = $logoPath;
            }

            /*
        |--------------------------------------------------------------------------
        | Office Images Upload
        |--------------------------------------------------------------------------
        */

            if ($request->hasFile('office_images')) {

                $officeImages = [];

                foreach ($request->file('office_images') as $image) {

                    $imageName = time() . '_' . uniqid() . '.' .
                        $image->getClientOriginalExtension();

                    $imagePath = $image->storeAs(
                        'firm/office-images',
                        $imageName,
                        'public'
                    );

                    $officeImages[] = $imagePath;
                }

                $updateData['office_images'] = json_encode($officeImages);
            }

            /*
        |--------------------------------------------------------------------------
        | Insert Or Update Firm Profile
        |--------------------------------------------------------------------------
        */

            if ($firmProfile) {

                DB::table('firm_profiles')
                    ->where('user_id', $userId)
                    ->update($updateData);

                $firmId = $firmProfile->id;


                /*
        |--------------------------------------------------------------------------
        | Update Users Table
        |--------------------------------------------------------------------------
        */

                DB::table('users')
                    ->where('id', $userId)
                    ->update($userUpdateData);
            } else {

                $updateData['created_at'] = now();

                $firmId = DB::table('firm_profiles')
                    ->insertGetId($updateData);
            }

            /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

            if ($request->has('departments')) {

                DB::table('firm_departments')
                    ->where('firm_id', $firmId)
                    ->delete();

                $departments = json_decode(
                    $request->departments,
                    true
                );

                if (!empty($departments)) {

                    foreach ($departments as $department) {

                        DB::table('firm_departments')
                            ->insert([
                                'firm_id' => $firmId,
                                'department_name' => $department,
                            ]);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */

            if ($request->has('branches')) {

                DB::table('firm_branches')
                    ->where('firm_id', $firmId)
                    ->delete();

                $branches = json_decode(
                    $request->branches,
                    true
                );

                if (!empty($branches)) {

                    foreach ($branches as $branch) {

                        DB::table('firm_branches')
                            ->insert([
                                'firm_id' => $firmId,
                                'city' => $branch['city'] ?? null,
                                'address' => $branch['address'] ?? null,
                                'state' => $branch['state'] ?? null,
                                'pincode' => $branch['pincode'] ?? null,
                            ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Firm profile updated successfully',
                'firm_id' => $firmId,
                'profile_image' => isset($userUpdateData['profile_image'])
                    ? asset('storage/' . $userUpdateData['profile_image'])
                    : null,
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Firm profile update: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Firm profile update failed: Server error'
            ]);
        }
    }


    public function getFirmProfileDetails(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');
            $userId = $user->id;

            /*
        |--------------------------------------------------------------------------
        | Get Firm Profile
        |--------------------------------------------------------------------------
        */

            $firmProfile = DB::table('firm_profiles')
                ->where('user_id', $userId)
                ->first();

            if (!$firmProfile) {

                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found',
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Get User Details
        |--------------------------------------------------------------------------
        */

            $user = DB::table('users')
                ->where('id', $userId)
                ->first();

            /*
        |--------------------------------------------------------------------------
        | Get Departments
        |--------------------------------------------------------------------------
        */

            $departments = DB::table('firm_departments')
                ->where('firm_id', $firmProfile->id)
                ->pluck('department_name');

            /*
        |--------------------------------------------------------------------------
        | Get Branches
        |--------------------------------------------------------------------------
        */

            $branches = DB::table('firm_branches')
                ->where('firm_id', $firmProfile->id)
                ->get();

            /*
        |--------------------------------------------------------------------------
        | Prepare Response
        |--------------------------------------------------------------------------
        */

            $response = [

                /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

                'userId' => $userId,

                'firm_name' => $firmProfile->firm_name,
                'frn' => $firmProfile->frn,
                'hr_name' => $firmProfile->hr_name,

                'email' => $user->email ?? null,
                'mobile' => $user->mobile ?? null,

                /*
            |--------------------------------------------------------------------------
            | Firm Details
            |--------------------------------------------------------------------------
            */

                'about' => $firmProfile->about,
                'firm_type' => $firmProfile->firm_type,

                'employees' => $firmProfile->employees_count,
                'partners' => $firmProfile->partners_count,
                'articles' => $firmProfile->articles_count,

                'establishment_year' =>
                $firmProfile->establishment_year,

                'services_offered' =>
                $firmProfile->services_offered,

                'industries_served' =>
                $firmProfile->industries_served,

                /*
            |--------------------------------------------------------------------------
            | Exposure & Opportunities
            |--------------------------------------------------------------------------
            */

                'exposure_type' =>
                json_decode(
                    $firmProfile->exposure_type ?? '[]',
                    true
                ),

                'departments' => $departments,

                'work_modes' =>
                json_decode(
                    $firmProfile->work_modes ?? '[]',
                    true
                ),

                'training_details' =>
                $firmProfile->training_details,

                'stipend_details' =>
                $firmProfile->stipend_details,

                'additional_contacts' => json_decode($firmProfile->additional_contacts ?? '[]', true),
                /*
            |--------------------------------------------------------------------------
            | Online Presence
            |--------------------------------------------------------------------------
            */

                'linkedin_url' =>
                $firmProfile->linkedin_url,

                'website_url' =>
                $firmProfile->website_url,

                'instagram_url' =>
                $firmProfile->instagram_url,

                'facebook_url' =>
                $firmProfile->facebook_url,

                'other_links' =>
                $firmProfile->other_links,

                /*
            |--------------------------------------------------------------------------
            | Uploads
            |--------------------------------------------------------------------------
            */

                'logo_path' =>
                $firmProfile->logo_path
                    ? asset('storage/' . $firmProfile->logo_path)
                    : null,

                'office_images' =>
                $firmProfile->office_images
                    ? array_map(function ($image) {
                        return asset('storage/' . $image);
                    }, json_decode($firmProfile->office_images, true))
                    : [],

                /*
            |--------------------------------------------------------------------------
            | Branches
            |--------------------------------------------------------------------------
            */

                'branches' => $branches,
            ];

            return response()->json([
                'status' => true,
                'message' => 'Firm profile details fetched successfully',
                'data' => $response,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
