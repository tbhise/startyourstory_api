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
                    'message' => $validator->errors()->first()
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
                'address' => $request->address,
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


            $isProfileCompleted =
                !empty($request->firm_name) &&
                !empty($request->hr_name) &&
                !empty($request->address) &&
                !empty($request->frn) &&
                !empty($request->firm_type) &&
                !empty($request->about) &&
                !empty($request->mobile) &&
                !empty($request->exposure_type) &&
                !empty($request->email);


            $userUpdateData = [
                'name' => $request->firm_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'profile_completed' => $isProfileCompleted ? 1 : 0,
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
                'address' => $firmProfile->address ?? null,
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
    public function getCompanies(Request $request)
    {
        try {
            $query = DB::table('firm_profiles')
                ->leftJoin(
                    'firm_departments',
                    'firm_profiles.id',
                    '=',
                    'firm_departments.firm_id'
                )
                ->select(
                    'firm_profiles.*',
                    DB::raw('GROUP_CONCAT(firm_departments.department_name) as departments')
                )
                ->groupBy('firm_profiles.id');
            /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
            if (!empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('firm_profiles.firm_name', 'LIKE', "%{$search}%")
                        ->orWhere('firm_profiles.city', 'LIKE', "%{$search}%")
                        ->orWhere('firm_profiles.about', 'LIKE', "%{$search}%")
                        ->orWhere('firm_profiles.services_offered', 'LIKE', "%{$search}%");
                });
            }
            /*
        |--------------------------------------------------------------------------
        | City Filter
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->cities) &&
                is_array($request->cities)
            ) {
                $query->whereIn(
                    'firm_profiles.city',
                    $request->cities
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Firm Type Filter
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->firmTypes) &&
                is_array($request->firmTypes)
            ) {
                $query->whereIn(
                    'firm_profiles.firm_type',
                    $request->firmTypes
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Exposure Type Filter
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->exposure) &&
                is_array($request->exposure)
            ) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->exposure as $exposure) {
                        if (
                            strtolower($exposure) === 'overall'
                        ) {
                            $q->orWhere(
                                'firm_profiles.exposure_type',
                                'LIKE',
                                '%"overall"%'
                            );
                        }
                        if (
                            strtolower($exposure) === 'department wise'
                        ) {
                            $q->orWhere(
                                'firm_profiles.exposure_type',
                                'NOT LIKE',
                                '%"overall"%'
                            );
                        }
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Department
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->departments) &&
                is_array($request->departments)
            ) {
                foreach ($request->departments as $department) {
                    $query->where(
                        'firm_profiles.exposure_type',
                        'LIKE',
                        "%$department%"
                    );
                };
            }
            /*
        |--------------------------------------------------------------------------
        | Work Modes Filter
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->workModes) &&
                is_array($request->workModes)
            ) {
                foreach ($request->workModes as $mode) {
                    $query->where(
                        'firm_profiles.work_modes',
                        'LIKE',
                        '%' . $mode . '%'
                    );
                }
            }
            /*
        |--------------------------------------------------------------------------
        | Employee
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->empRanges) &&
                is_array($request->empRanges)
            ) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->empRanges as $range) {
                        if (str_contains($range, '-')) {
                            [$min, $max] = explode('-', $range);
                            $q->orWhereBetween(
                                'firm_profiles.employees_count',
                                [(int) $min, (int) $max]
                            );
                        } elseif (str_contains($range, '+')) {
                            $min = (int) str_replace('+', '', $range);
                            $q->orWhere(
                                'firm_profiles.employees_count',
                                '>=',
                                $min
                            );
                        }
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Article Count
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->artRanges) &&
                is_array($request->artRanges)
            ) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->artRanges as $range) {
                        if (str_contains($range, '-')) {
                            [$min, $max] = explode('-', $range);
                            $q->orWhereBetween(
                                'firm_profiles.articles_count',
                                [(int) $min, (int) $max]
                            );
                        } elseif (str_contains($range, '+')) {
                            $min = (int) str_replace('+', '', $range);
                            $q->orWhere(
                                'firm_profiles.articles_count',
                                '>=',
                                $min
                            );
                        }
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Departments Filter
        |--------------------------------------------------------------------------
        */
            if (
                !empty($request->exposure_type) &&
                is_array($request->exposure_type)
            ) {
                $query->whereIn(
                    'firm_profiles.exposure_type',
                    $request->exposure_type
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
            if (!empty($request->sort)) {
                switch ($request->sort) {
                    case 'premium':
                        $query->orderBy(
                            'firm_profiles.is_premium',
                            'DESC'
                        );
                        break;
                    case 'az':
                        $query->orderBy(
                            'firm_profiles.firm_name',
                            'ASC'
                        );
                        break;
                    case 'employees':
                        $query->orderBy(
                            'firm_profiles.employees_count',
                            'DESC'
                        );
                        break;
                    case 'oldest':
                        $query->orderBy(
                            'firm_profiles.created_at',
                            'ASC'
                        );
                        break;
                    default:
                        $query->orderBy(
                            'firm_profiles.created_at',
                            'DESC'
                        );
                        break;
                }
            } else {
                $query->orderBy(
                    'firm_profiles.created_at',
                    'DESC'
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Simple Pagination
        |--------------------------------------------------------------------------
        */
            $companies = $query->paginate(12);
            $formattedCompanies = [];
            foreach ($companies->items() as $company) {
                $formattedCompanies[] = [
                    'id' => $company->id,
                    'firm_name' => $company->firm_name,
                    'frn' => $company->frn,
                    'city' => $company->city,
                    'address' => $company->address,
                    'hr_name' => $company->hr_name,
                    'partners_count' => $company->partners_count,
                    'employees_count' => $company->employees_count,
                    'articles_count' => $company->articles_count,
                    'exposure_type' =>
                    json_decode($company->exposure_type, true) ?? [],
                    'linkedin_url' => $company->linkedin_url,
                    'website_url' => $company->website_url,
                    'logo_path' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                    'is_premium' => $company->is_premium,
                    'about' => $company->about,
                    'firm_type' => $company->firm_type,
                    'establishment_year' =>
                    $company->establishment_year,
                    'services_offered' =>
                    $company->services_offered
                        ? array_map(
                            'trim',
                            explode(',', $company->services_offered)
                        )
                        : [],
                    'industries_served' =>
                    $company->industries_served,
                    'work_modes' =>
                    json_decode($company->work_modes, true) ?? [],
                    'training_details' =>
                    $company->training_details,
                    'stipend_details' =>
                    $company->stipend_details,
                    'instagram_url' =>
                    $company->instagram_url,
                    'facebook_url' =>
                    $company->facebook_url,
                    'other_links' =>
                    $company->other_links,
                    'office_images' =>
                    json_decode($company->office_images, true) ?? [],
                    'additional_contacts' =>
                    json_decode($company->additional_contacts, true) ?? [],
                    'departments' =>
                    $company->departments
                        ? explode(',', $company->departments)
                        : [],
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Companies fetched successfully',
                'data' => [
                    'companies' => $formattedCompanies,
                    'next_page_url' => $companies->nextPageUrl(),
                    'prev_page_url' => $companies->previousPageUrl(),
                    'current_page' => $companies->currentPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Companies API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Server error.',
            ]);
        }
    }
    public function getCompanyDetails(Request $request)
    {
        try {
            $id = $request->id;
            $company = DB::table('firm_profiles')
                ->select('firm_profiles.*', 'users.mobile as primary_mobile')
                ->leftJoin('users', 'users.id', 'firm_profiles.user_id')
                ->where('firm_profiles.id', $id)
                ->first();
            if (!$company) {
                return response()->json([
                    'status' => false,
                    'message' => 'Company not found',
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */
            $branches = DB::table('firm_branches')
                ->where('firm_id', $company->id)
                ->get()
                ->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'city' => $branch->city,
                        'address' => $branch->address,
                        'state' => $branch->state,
                        'pincode' => $branch->pincode,
                    ];
                });
            /*
        |--------------------------------------------------------------------------
        | Office Images
        |--------------------------------------------------------------------------
        */
            $officeImages = [];
            if (!empty($company->office_images)) {
                $images = json_decode(
                    $company->office_images,
                    true
                ) ?? [];
                $officeImages = array_map(function ($img) {
                    return asset('/storage/' . $img);
                }, $images);
            }
            /*
        |--------------------------------------------------------------------------
        | Logo
        |--------------------------------------------------------------------------
        */
            $logoPath = null;
            if (!empty($company->logo_path)) {
                $logoPath = asset('/storage/' . $company->logo_path);
            }
            /*
        |--------------------------------------------------------------------------
        | Exposure Type / Departments
        |--------------------------------------------------------------------------
        */
            $exposureType = json_decode(
                $company->exposure_type,
                true
            ) ?? [];
            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            $data = [
                'id' => $company->id,
                'user_id' => $company->user_id,
                'firm_name' => $company->firm_name,
                // 'frn' => $company->frn,
                'city' => $company->city,
                'address' => $company->address,
                'hr_name' => $company->hr_name,
                // 'partners_count' => $company->partners_count,
                'employees_count' => $company->employees_count,
                'articles_count' => $company->articles_count,
                'about' => $company->about,
                // 'firm_type' => $company->firm_type,
                'establishment_year' =>
                $company->establishment_year,
                'services_offered' =>
                !empty($company->services_offered)
                    ? array_map(
                        'trim',
                        explode(
                            ',',
                            $company->services_offered
                        )
                    )
                    : [],
                'industries_served' =>
                !empty($company->industries_served)
                    ? array_map(
                        'trim',
                        explode(
                            ',',
                            $company->industries_served
                        )
                    )
                    : [],
                'work_modes' =>
                json_decode(
                    $company->work_modes,
                    true
                ) ?? [],
                'training_details' =>
                $company->training_details,
                'stipend_details' =>
                $company->stipend_details,
                'linkedin_url' =>
                $company->linkedin_url,
                'website_url' =>
                $company->website_url,
                'instagram_url' =>
                $company->instagram_url,
                'facebook_url' =>
                $company->facebook_url,
                'other_links' =>
                json_decode(
                    $company->other_links,
                    true
                ) ?? [],
                'is_premium' =>
                $company->is_premium,
                'logo_path' => $logoPath,
                'office_images' => $officeImages,
                /*
            |--------------------------------------------------------------------------
            | Using exposure_type as departments for now
            |--------------------------------------------------------------------------
            */
                'departments' => $exposureType,
                'exposure_type' => $exposureType,
                'branches' => $branches,
                // 'additional_contacts' =>
                // json_decode(
                //     $company->additional_contacts,
                //     true
                // ) ?? [],
                // 'primary_mobile' => $company->primary_mobile
                'primary_mobile' => !empty($company->primary_mobile)
                    ? substr($company->primary_mobile, 0, 2)
                    . 'XXXXXX'
                    . substr($company->primary_mobile, -2)
                    : null,
                'additional_contacts' => collect(
                    json_decode(
                        $company->additional_contacts,
                        true
                    ) ?? []
                )->map(function ($contact) {
                    $contact['phone'] = !empty($contact['phone'])
                        ? substr($contact['phone'], 0, 2)
                        . 'XXXXXX'
                        . substr($contact['phone'], -2)
                        : null;
                    return $contact;
                })->values(),
            ];
            return response()->json([
                'status' => true,
                'message' =>
                'Company details fetched successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Company Details Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Server error.',
            ]);
        }
    }
    public function createJob(Request $request)
    {
        DB::beginTransaction();
        try {
            /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */
            $validator = Validator::make($request->all(), [
                //'firm_id' => 'required|integer',
                'title' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'salary' => 'required|string|max:255',
                'description' => 'required|string',
                'required_qualification' => 'required|string',
                'department' => 'nullable|string|max:255',
                'work_mode' => 'nullable|string|max:100',
                'experience_level' => 'nullable|string|max:100',
                'openings' => 'nullable|integer',
                'required_skills' => 'nullable|array',
                'required_skills.*' => 'nullable|string|max:100',
                'benefits' => 'nullable|string',
                'application_deadline' => 'nullable|date',
                'status' => 'nullable|in:Draft,Active,Closed',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Check Firm Exists
        |--------------------------------------------------------------------------
        */
            $user = $request->attributes->get('auth_user');
            $userId = $user->id;
            $firm = DB::table('firm_profiles')
                ->where('user_id', $userId)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Insert Job
        |--------------------------------------------------------------------------
        */
            $jobId = DB::table('jobs')->insertGetId([
                'firm_id' => $firm->id,
                'title' => $request->title,
                'location' => $request->location,
                'type' => $request->type,
                'salary' => $request->salary,
                'description' => $request->description,
                'department' => $request->department,
                'work_mode' => $request->work_mode,
                'experience_level' => $request->experience_level,
                'openings' => $request->openings,
                'required_skills' =>
                !empty($request->required_skills)
                    ? json_encode($request->required_skills)
                    : null,
                'benefits' => $request->benefits,
                'required_qualification' =>
                $request->required_qualification,
                'application_deadline' =>
                $request->application_deadline,
                'status' =>
                $request->status,
                'is_active' =>
                $request->status === 'Closed'
                    ? 0
                    : 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            /*
        |--------------------------------------------------------------------------
        | Fetch Created Job
        |--------------------------------------------------------------------------
        */
            $job = DB::table('jobs')->where('id', $jobId)->first();
            DB::commit();
            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' => 'Job created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create Job API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Server error',
            ]);
        }
    }

    public function getFirmJobs(Request $request)
    {
        try {

            $user = $request->attributes->get('auth_user');


            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found',
                ]);
            }

            $query = DB::table('jobs')->where('firm_id', $firm->id);

            if (!empty($request->search)) {


                $query->where(function ($q) use ($request) {

                    $search =
                        '%' . $request->search . '%';

                    $q->where(
                        'title',
                        'LIKE',
                        $search
                    )
                        ->orWhere(
                            'location',
                            'LIKE',
                            $search
                        )
                        ->orWhere(
                            'department',
                            'LIKE',
                            $search
                        );
                });
            }

            if (
                !empty($request->status) && is_array($request->status)
            ) {
                $query->whereIn('status', $request->status);
            }

            if (!empty($request->type) && is_array($request->type)) {
                $query->whereIn('type', $request->type);
            }

            if (!empty($request->department) && is_array($request->department)) {
                $query->whereIn('department', $request->department);
            }

            if (!empty($request->work_mode) && is_array($request->work_mode)) {
                $query->whereIn('work_mode', $request->work_mode);
            }

            if ($request->sort === 'oldest') {
                $query->orderBy('created_at', 'asc');
            } else if ($request->sort === 'active') {
                $query->orderBy('is_active');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $jobs = $query->paginate(10);

            $data = collect($jobs->items())->map(function ($job) {
                return [
                    'id' => $job->id,
                    'firm_id' => $job->firm_id,
                    'title' => $job->title,
                    'location' => $job->location,
                    'type' => $job->type,
                    'salary' => $job->salary,
                    'description' => $job->description,
                    'department' => $job->department,
                    'work_mode' => $job->work_mode,
                    'experience_level' =>
                    $job->experience_level,
                    'openings' => $job->openings,
                    'required_skills' => json_decode($job->required_skills, true) ?? [],
                    'benefits' => $job->benefits,
                    'required_qualification' =>
                    $job->required_qualification,
                    'application_deadline' =>
                    $job->application_deadline,
                    'status' => $job->status,
                    'is_active' => $job->is_active,
                    'created_at' => $job->created_at ? date('d M Y g:i A', strtotime($job->created_at)) : null,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Firm jobs fetched successfully',
                'data' => [
                    'jobs' => $data,
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'next_page_url' => $jobs->nextPageUrl(),
                    'prev_page_url' => $jobs->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Firm Jobs API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error while fetching job list.',
            ]);
        }
    }
    /**
     *
     *
     */


    public function getFirmJobDetails(Request $request, $id = null)
    {
        try {
            $user = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found',
                ]);
            }
            $job = DB::table('jobs')
                ->where('firm_id', $firm->id)
                ->where('id', $id)
                ->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found',
                ]);
            }
            $formatDate = fn($date) =>
            $date
                ? date(
                    'd M Y g:i A',
                    strtotime($date)
                )
                : null;
            return response()->json([
                'status' => true,
                'message' => 'Job details fetched successfully',
                'data' => [
                    'id' => $job->id,
                    'firm_id' => $job->firm_id,
                    'title' => $job->title,
                    'location' => $job->location,
                    'type' => $job->type,
                    'salary' => $job->salary,
                    'description' => $job->description,
                    'department' => $job->department,
                    'work_mode' => $job->work_mode,
                    'experience_level' => $job->experience_level,
                    'openings' => $job->openings,
                    'required_skills' => json_decode($job->required_skills, true) ?? [],
                    'benefits' => $job->benefits,
                    'required_qualification' =>
                    $job->required_qualification,
                    'application_deadline' =>
                    $job->application_deadline,
                    'status' => $job->status,
                    'is_active' => $job->is_active,
                    'created_at' =>  $formatDate($job->created_at),
                    'updated_at' =>  $formatDate($job->updated_at),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Firm Job Details API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while fetching details.',
            ]);
        }
    }
    public function updateJobStatus(Request $request, $id = null)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:Draft,Active,Closed',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            $user = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ]);
            }
            $updated = DB::table('jobs')
                ->where('id', $id)
                ->where('firm_id', $firm->id)
                ->update([
                    'status' => $request->status,
                    'is_active' => strtolower($request->status) === 'closed' ? 0 : 1,
                    'updated_at' => now(),
                ]);
            if (!$updated) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found'
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Job status updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Job Status API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while updating job status.',
            ]);
        }
    }
    public function deleteFirmJob(Request $request, $id = null)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ]);
            }
            $deleted = DB::table('jobs')
                ->where('id', $id)
                ->where('firm_id', $firm->id)
                ->delete();
            if (!$deleted) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found'
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Job deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Firm Job API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while deleting job.',
            ]);
        }
    }
    public function updateJob(Request $request, $id = NULL)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'salary' => 'required|string|max:255',
                'description' => 'required|string',
                'required_qualification' => 'required|string',
                'department' => 'nullable|string|max:255',
                'work_mode' => 'nullable|string|max:100',
                'experience_level' => 'nullable|string|max:100',
                'openings' => 'nullable|integer',
                'required_skills' => 'nullable|array',
                'required_skills.*' => 'nullable|string|max:100',
                'benefits' => 'nullable|string',
                'application_deadline' => 'nullable|date',
                'status' => 'nullable|in:Draft,Active,Closed',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            $user = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ]);
            }
            $job = DB::table('jobs')
                ->where('id', $id)
                ->where('firm_id', $firm->id)
                ->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found'
                ]);
            }
            $status = $request->status ?? $job->status;
            DB::table('jobs')
                ->where('id', $id)
                ->update([
                    'title' => $request->title,
                    'location' => $request->location,
                    'type' => $request->type,
                    'salary' => $request->salary,
                    'description' => $request->description,
                    'department' => $request->department,
                    'work_mode' => $request->work_mode,
                    'experience_level' => $request->experience_level,
                    'openings' => $request->openings,
                    'required_skills' => !empty($request->required_skills) ? json_encode($request->required_skills) : null,
                    'benefits' => $request->benefits,
                    'required_qualification' => $request->required_qualification,
                    'application_deadline' => $request->application_deadline,
                    'status' => $status,
                    'is_active' => strtolower($status) === 'closed' ? 0 : 1,
                    'updated_at' => now(),
                ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Job updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Update Job API Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while updating job.',
            ]);
        }
    }
}
