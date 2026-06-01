<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrainingPartnerController extends Controller
{




    private function getAdminUser(Request $request)
    {
        $token = $request->cookie('admin_token');

        if (!$token) {
            return null;
        }

        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }
    public function index(Request $request)
    {
        $query = DB::table('training_partners')->select(
            '*',
            DB::raw("concat('" . url('/') . "/storage/', logo) as logo"),
            DB::raw("concat('" . url('/') . "/storage/', banner_image) as banner_image")

        );

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $partners = $query
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $partners
        ]);
    }


    public function getActiveTrainingPartners()
    {
        $partners = DB::table('training_partners')
            ->select(
                '*',
                DB::raw("concat('" . url('/') . "/storage/', logo) as logo"),
                DB::raw("concat('" . url('/') . "/storage/', banner_image) as banner_image")
            )
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $partners
        ]);
    }







    public function show($id)
    {
        $partner = DB::table('training_partners')
            ->where('id', $id)
            ->first();

        if (!$partner) {
            return response()->json([
                'status' => false,
                'message' => 'Training partner not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $partner
        ]);
    }

    public function store(Request $request)
    {
        try {

            $admin = $this->getAdminUser($request);

            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'institute_name' => 'required|max:255',
                    'short_description' => 'nullable|max:1000',
                    'website_url' => 'nullable|url',
                    'contact_person' => 'required|max:255',
                    'contact_number' => 'required|max:20',
                    'city' => 'required|max:100',
                    'state' => 'required|max:100',
                    'display_order' => 'nullable|integer',
                    'starts_at' => 'nullable|date',
                    'expires_at' => 'nullable|date',
                    'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                    'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                ],
                [
                    'logo.image' => 'Please upload a valid image file.',
                    'logo.mimes' => 'Logo must be in JPG, JPEG, PNG or WEBP format.',
                    'logo.max' => 'Logo image size must be less than 4 MB.',

                    'banner_image.image' => 'Please upload a valid image file.',
                    'banner_image.mimes' => 'Banner image must be in JPG, JPEG, PNG or WEBP format.',
                    'banner_image.max' => 'Banner image size must be less than 4 MB.',
                ]
            );


            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }




            $logo = null;
            $banner = null;

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo')
                    ->store('training-partners/logo', 'public');
            }

            if ($request->hasFile('banner_image')) {
                $banner = $request->file('banner_image')
                    ->store('training-partners/banner', 'public');
            }

            $id = DB::table('training_partners')->insertGetId([
                'institute_name' => $request->institute_name,
                'logo' => $logo,
                'banner_image' => $banner,
                'short_description' => $request->short_description,
                'website_url' => $request->website_url,
                'contact_person' => $request->contact_person,
                'contact_number' => $request->contact_number,
                'city' => $request->city,
                'state' => $request->state,
                'display_order' => $request->display_order ?? 0,
                'starts_at' => $request->starts_at
                    ? date('Y-m-d', strtotime($request->starts_at))
                    : null,
                'expires_at' => $request->expires_at
                    ? date('Y-m-d', strtotime($request->expires_at))
                    : null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Training partner created successfully.',
                'id' => $id
            ]);
        } catch (\Throwable $e) {

            Log::error('Training Partner Create Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {

            $admin = $this->getAdminUser($request);

            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if ($request->hasFile('logo') && !$request->file('logo')->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Logo image upload failed. Please upload a JPG, PNG or WEBP image smaller than 2 MB.'
                ], 422);
            }

            if ($request->hasFile('banner_image') && !$request->file('banner_image')->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Banner image upload failed. Please upload a JPG, PNG or WEBP image smaller than 4 MB.'
                ], 422);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'institute_name' => 'required|max:255',
                    'short_description' => 'nullable|max:1000',
                    'website_url' => 'nullable|url',
                    'contact_person' => 'required|max:255',
                    'contact_number' => 'required|max:20',
                    'city' => 'required|max:100',
                    'state' => 'required|max:100',
                    'display_order' => 'nullable|integer',
                    'starts_at' => 'nullable|date',
                    'expires_at' => 'nullable|date',
                    'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                    'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                ],
                [
                    'logo.image' => 'Please upload a valid image file.',
                    'logo.mimes' => 'Logo must be in JPG, JPEG, PNG or WEBP format.',
                    'logo.max' => 'Logo image size must be less than 4 MB.',

                    'banner_image.image' => 'Please upload a valid image file.',
                    'banner_image.mimes' => 'Banner image must be in JPG, JPEG, PNG or WEBP format.',
                    'banner_image.max' => 'Banner image size must be less than 4 MB.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $partner = DB::table('training_partners')
                ->where('id', $id)
                ->first();

            if (!$partner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Training partner not found.'
                ], 404);
            }

            $data = [
                'institute_name' => $request->institute_name,
                'short_description' => $request->short_description,
                'website_url' => $request->website_url,
                'contact_person' => $request->contact_person,
                'contact_number' => $request->contact_number,
                'city' => $request->city,
                'state' => $request->state,
                'display_order' => $request->display_order,
                'starts_at' => $request->starts_at ? date('Y-m-d', strtotime($partner->starts_at)) : null,
                'expires_at' => $request->expires_at ? date('Y-m-d', strtotime($partner->expires_at)) : null,
                'updated_at' => now(),
            ];

            if ($request->hasFile('logo')) {

                if ($partner->logo) {
                    Storage::disk('public')->delete($partner->logo);
                }

                $data['logo'] = $request->file('logo')
                    ->store('training-partners/logo', 'public');
            }

            if ($request->hasFile('banner_image')) {

                if ($partner->banner_image) {
                    Storage::disk('public')->delete($partner->banner_image);
                }

                $data['banner_image'] = $request->file('banner_image')
                    ->store('training-partners/banner', 'public');
            }

            DB::table('training_partners')
                ->where('id', $id)
                ->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Training partner updated successfully.'
            ]);
        } catch (\Throwable $e) {

            Log::error('Training Partner Update Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        $partner = DB::table('training_partners')
            ->where('id', $id)
            ->first();

        if (!$partner) {
            return response()->json([
                'status' => false,
                'message' => 'Training partner not found.'
            ], 404);
        }

        if ($partner->logo) {
            Storage::disk('public')->delete($partner->logo);
        }

        if ($partner->banner_image) {
            Storage::disk('public')->delete($partner->banner_image);
        }

        DB::table('training_partners')
            ->where('id', $id)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Training partner deleted successfully.'
        ]);
    }

    public function toggleActive($id)
    {
        $partner = DB::table('training_partners')
            ->where('id', $id)
            ->first();

        if (!$partner) {
            return response()->json([
                'status' => false,
                'message' => 'Training partner not found.'
            ], 404);
        }

        $newStatus = !$partner->is_active;

        DB::table('training_partners')
            ->where('id', $id)
            ->update([
                'is_active' => $newStatus,
                'updated_at' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully.',
            'is_active' => $newStatus
        ]);
    }
}
