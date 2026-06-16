<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\AdminActivityLogger;

class AdminUserController extends Controller
{
    // ─── Auth helper ─────────────────────────────────────────────────────────

    private function resolveAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    // ─── Format helper ────────────────────────────────────────────────────────

    private function format(object $u): array
    {
        return [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'role'       => $u->role,
            'is_active'  => (bool) $u->is_active,
            'created_at' => $u->created_at,
            'updated_at' => $u->updated_at,
        ];
    }

    // ─── GET /admin/users ─────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $admin = $this->resolveAdmin($request);
            if (!$admin) return $this->unauthorized();

            $search = trim($request->input('search', ''));

            $query = DB::table('admin_users')->orderByDesc('id');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->get()->map(fn($u) => $this->format($u));

            return response()->json([
                'status' => true,
                'data'   => ['users' => $users],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminUserController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─── POST /admin/users ────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        try {
            $admin = $this->resolveAdmin($request);
            if (!$admin) return $this->unauthorized();

            $validator = Validator::make($request->all(), [
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|max:255',
                'password'              => 'required|string|min:8',
                'password_confirmation' => 'required|same:password',
                'role'                  => 'required|in:super_admin,admin,moderator',
                'is_active'             => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            // Unique email check
            $exists = DB::table('admin_users')->where('email', $request->email)->exists();
            if ($exists) {
                return response()->json(['status' => false, 'message' => 'An admin with this email already exists.'], 422);
            }

            // Additional password strength check (uppercase + digit)
            if (!preg_match('/[A-Z]/', $request->password) || !preg_match('/[0-9]/', $request->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Password must contain at least one uppercase letter and one number.',
                ], 422);
            }

            DB::beginTransaction();

            $id = DB::table('admin_users')->insertGetId([
                'name'       => trim($request->name),
                'email'      => strtolower(trim($request->email)),
                'password'   => Hash::make($request->password),
                'role'       => $request->role,
                'is_active'  => $request->boolean('is_active', true) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user = DB::table('admin_users')->where('id', $id)->first();

            DB::commit();

            $name  = trim($request->name);
            $email = strtolower(trim($request->email));
            AdminActivityLogger::log($admin, AdminActivityLogger::ADMIN_CREATED, 'admin_user', $id, "Created admin user '{$name}' ({$email}).", $request);

            return response()->json([
                'status'  => true,
                'message' => 'Admin user created successfully.',
                'data'    => ['user' => $this->format($user)],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminUserController@store: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─── POST /admin/users/{id} ───────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $admin = $this->resolveAdmin($request);
            if (!$admin) return $this->unauthorized();

            $target = DB::table('admin_users')->where('id', $id)->first();
            if (!$target) {
                return response()->json(['status' => false, 'message' => 'Admin user not found.'], 404);
            }

            $rules = [
                'name'  => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'role'  => 'required|in:super_admin,admin,moderator',
                'is_active' => 'sometimes|boolean',
            ];

            // Password only validated when provided
            if ($request->filled('password')) {
                $rules['password']              = 'string|min:8';
                $rules['password_confirmation'] = 'required_with:password|same:password';
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            // Unique email check (excluding self)
            $emailTaken = DB::table('admin_users')
                ->where('email', $request->email)
                ->where('id', '!=', $id)
                ->exists();
            if ($emailTaken) {
                return response()->json(['status' => false, 'message' => 'This email is already used by another admin.'], 422);
            }

            if ($request->filled('password')) {
                if (!preg_match('/[A-Z]/', $request->password) || !preg_match('/[0-9]/', $request->password)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Password must contain at least one uppercase letter and one number.',
                    ], 422);
                }
            }

            DB::beginTransaction();

            $data = [
                'name'       => trim($request->name),
                'email'      => strtolower(trim($request->email)),
                'role'       => $request->role,
                'is_active'  => $request->boolean('is_active', (bool) $target->is_active) ? 1 : 0,
                'updated_at' => now(),
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
                // Revoke existing token so the user must log in again with the new password
                $data['api_token'] = null;
            }

            DB::table('admin_users')->where('id', $id)->update($data);

            $updated = DB::table('admin_users')->where('id', $id)->first();

            DB::commit();

            AdminActivityLogger::log($admin, AdminActivityLogger::ADMIN_UPDATED, 'admin_user', $id, "Updated admin user #{$id}.", $request);

            return response()->json([
                'status'  => true,
                'message' => 'Admin user updated successfully.',
                'data'    => ['user' => $this->format($updated)],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminUserController@update: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─── DELETE /admin/users/{id} ─────────────────────────────────────────────

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $admin = $this->resolveAdmin($request);
            if (!$admin) return $this->unauthorized();

            $target = DB::table('admin_users')->where('id', $id)->first();
            if (!$target) {
                return response()->json(['status' => false, 'message' => 'Admin user not found.'], 404);
            }

            // Prevent self-deletion
            if ((int) $admin->id === (int) $id) {
                return response()->json(['status' => false, 'message' => 'You cannot delete your own account.'], 422);
            }

            // Prevent deleting the last active admin
            $activeCount = DB::table('admin_users')->where('is_active', true)->count();
            if ($activeCount <= 1 && $target->is_active) {
                return response()->json(['status' => false, 'message' => 'Cannot delete the last active admin account.'], 422);
            }

            DB::beginTransaction();

            DB::table('admin_users')->where('id', $id)->delete();

            DB::commit();

            AdminActivityLogger::log($admin, AdminActivityLogger::ADMIN_DELETED, 'admin_user', $id, "Deleted admin user #{$id}.", $request);

            return response()->json([
                'status'  => true,
                'message' => 'Admin user deleted.',
                'data'    => ['ok' => true],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminUserController@destroy: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─── POST /admin/users/{id}/toggle-active ─────────────────────────────────

    public function toggleActive(Request $request, $id): JsonResponse
    {
        try {
            $admin = $this->resolveAdmin($request);
            if (!$admin) return $this->unauthorized();

            $target = DB::table('admin_users')->where('id', $id)->first();
            if (!$target) {
                return response()->json(['status' => false, 'message' => 'Admin user not found.'], 404);
            }

            // Prevent deactivating self
            if ((int) $admin->id === (int) $id && !$request->boolean('is_active', true)) {
                return response()->json(['status' => false, 'message' => 'You cannot deactivate your own account.'], 422);
            }

            $newActive = $request->boolean('is_active', !(bool) $target->is_active);

            // Prevent deactivating the last active admin
            if (!$newActive) {
                $activeCount = DB::table('admin_users')->where('is_active', true)->count();
                if ($activeCount <= 1 && $target->is_active) {
                    return response()->json(['status' => false, 'message' => 'Cannot deactivate the last active admin.'], 422);
                }
            }

            DB::table('admin_users')->where('id', $id)->update([
                'is_active'  => $newActive ? 1 : 0,
                'api_token'  => $newActive ? $target->api_token : null, // revoke token on deactivate
                'updated_at' => now(),
            ]);

            $updated = DB::table('admin_users')->where('id', $id)->first();

            AdminActivityLogger::log($admin, $newActive ? AdminActivityLogger::ADMIN_ENABLED : AdminActivityLogger::ADMIN_DISABLED, 'admin_user', $id, ($newActive ? "Enabled" : "Disabled")." admin user #{$id}.", $request);

            return response()->json([
                'status'  => true,
                'message' => $newActive ? 'Admin user activated.' : 'Admin user deactivated.',
                'data'    => ['user' => $this->format($updated)],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminUserController@toggleActive: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
