<?php

namespace App\Http\Controllers\API;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Services\Notifications\EmailNotificationService;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Helpers\SubscriptionHelper;
use Illuminate\Support\Str;

class CreatorMarketplaceController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Dashboard stats
    // ─────────────────────────────────────────────────────────────────────────

    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $user     = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $firmId = $firmProfile->id;

            $activeProjects    = DB::table('creator_projects')
                ->where('firm_id', $firmId)
                ->where('status', 'published')
                ->count();

            $openProjects      = DB::table('creator_projects')
                ->where('firm_id', $firmId)
                ->whereIn('status', ['draft', 'published'])
                ->count();

            $completedProjects = DB::table('creator_projects')
                ->where('firm_id', $firmId)
                ->where('status', 'closed')
                ->count();

            $totalBids = DB::table('creator_project_bids')
                ->join('creator_projects', 'creator_project_bids.project_id', '=', 'creator_projects.id')
                ->where('creator_projects.firm_id', $firmId)
                ->count();

            $selectedCreators = DB::table('creator_project_bids')
                ->join('creator_projects', 'creator_project_bids.project_id', '=', 'creator_projects.id')
                ->where('creator_projects.firm_id', $firmId)
                ->where('creator_project_bids.status', 'selected')
                ->count();

            $recentProjects = DB::table('creator_projects')
                ->where('firm_id', $firmId)
                ->whereNotIn('status', ['cancelled'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'title', 'category', 'status', 'budget_type', 'budget_min', 'budget_max', 'delivery_days', 'created_at']);

            foreach ($recentProjects as $p) {
                $p->bid_count = DB::table('creator_project_bids')
                    ->where('project_id', $p->id)
                    ->count();
            }

            return response()->json([
                'status'  => true,
                'message' => 'Dashboard stats loaded',
                'data'    => [
                    'active_projects'    => $activeProjects,
                    'open_projects'      => $openProjects,
                    'total_bids'         => $totalBids,
                    'selected_creators'  => $selectedCreators,
                    'completed_projects' => $completedProjects,
                    'recent_projects'    => $recentProjects,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getDashboardStats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Create project
    // ─────────────────────────────────────────────────────────────────────────

    public function createProject(Request $request): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'title'           => 'required|string|max:255',
                'description'     => 'required|string',
                'category'        => 'required|string|max:100',
                'budget_type'     => 'required|in:fixed,range,negotiable',
                'budget_min'      => 'nullable|numeric|min:0',
                'budget_max'      => 'nullable|numeric|min:0',
                'delivery_days'   => 'nullable|integer|min:1',
                'skills_required' => 'nullable|array',
                'skills_required.*' => 'string|max:100',
                'status'          => 'nullable|in:draft,published',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $status      = $request->input('status', 'draft');
            $publishedAt = $status === 'published' ? now() : null;

            $id = DB::table('creator_projects')->insertGetId([
                'firm_id'         => $firmProfile->id,
                'title'           => $request->title,
                'slug'            => $this->generateSlug($request->title),
                'description'     => $request->description,
                'category'        => $request->category,
                'budget_type'     => $request->budget_type,
                'budget_min'      => $request->budget_min,
                'budget_max'      => $request->budget_max,
                'delivery_days'   => $request->delivery_days,
                'skills_required' => $request->skills_required ? json_encode($request->skills_required) : null,
                'attachments'     => null,
                'status'          => $status,
                'published_at'    => $publishedAt,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => $status === 'published' ? 'Project published successfully' : 'Project saved as draft',
                'data'    => ['id' => $id],
            ], 201);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@createProject: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Update project
    // ─────────────────────────────────────────────────────────────────────────

    public function updateProject(Request $request, $id): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
        }

        $project = DB::table('creator_projects')
            ->where('id', $id)
            ->where('firm_id', $firmProfile->id)
            ->first();

        if (! $project) {
            return response()->json(['status' => false, 'message' => 'Project not found'], 404);
        }

        if (in_array($project->status, ['closed', 'cancelled'])) {
            return response()->json(['status' => false, 'message' => 'Cannot edit a closed or cancelled project'], 422);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'sometimes|required|string|max:255',
            'description'     => 'sometimes|required|string',
            'category'        => 'sometimes|required|string|max:100',
            'budget_type'     => 'sometimes|required|in:fixed,range,negotiable',
            'budget_min'      => 'nullable|numeric|min:0',
            'budget_max'      => 'nullable|numeric|min:0',
            'delivery_days'   => 'nullable|integer|min:1',
            'skills_required' => 'nullable|array',
            'skills_required.*' => 'string|max:100',
            'status'          => 'nullable|in:draft,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updates = ['updated_at' => now()];

        foreach (['title', 'description', 'category', 'budget_type', 'budget_min', 'budget_max', 'delivery_days'] as $field) {
            if ($request->has($field)) {
                $updates[$field] = $request->input($field);
            }
        }

        if ($request->has('skills_required')) {
            $updates['skills_required'] = $request->skills_required ? json_encode($request->skills_required) : null;
        }

        if ($request->has('status')) {
            $newStatus = $request->status;
            $updates['status'] = $newStatus;
            if ($newStatus === 'published' && $project->status === 'draft') {
                $updates['published_at'] = now();
            }
        }

        DB::table('creator_projects')->where('id', $id)->update($updates);

        return response()->json(['status' => true, 'message' => 'Project updated successfully']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@updateProject: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — List my projects
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyProjects(Request $request): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
        }

        $query = DB::table('creator_projects')
            ->where('firm_id', $firmProfile->id);

        if ($request->filled('tab')) {
            $tabStatuses = match ($request->tab) {
                'open'      => ['draft', 'published'],
                'completed' => ['closed'],
                default     => null,
            };
            if ($tabStatuses) {
                $query->whereIn('status', $tabStatuses);
            }
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)->orWhere('category', 'like', $s);
            });
        }

        $query->orderByDesc('created_at');

        $projects = $query->get([
            'id', 'title', 'slug', 'category', 'status', 'budget_type',
            'budget_min', 'budget_max', 'delivery_days', 'published_at',
            'closed_at', 'created_at',
        ]);

        foreach ($projects as $p) {
            $p->skills_required = null;
            $p->bid_count = DB::table('creator_project_bids')
                ->where('project_id', $p->id)
                ->count();
            $p->shortlisted_count = DB::table('creator_project_bids')
                ->where('project_id', $p->id)
                ->where('status', 'shortlisted')
                ->count();
            $p->selected_count = DB::table('creator_project_bids')
                ->where('project_id', $p->id)
                ->where('status', 'selected')
                ->count();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Projects loaded',
            'data'    => ['projects' => $projects],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getMyProjects: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Project detail (with bid summary)
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyProjectDetails(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $project = DB::table('creator_projects')
                ->where('id', $id)
                ->where('firm_id', $firmProfile->id)
                ->first();

            if (! $project) {
                return response()->json(['status' => false, 'message' => 'Project not found'], 404);
            }

            $project->skills_required = $project->skills_required ? json_decode($project->skills_required) : [];

            $project->bid_stats = [
                'total'       => DB::table('creator_project_bids')->where('project_id', $id)->count(),
                'pending'     => DB::table('creator_project_bids')->where('project_id', $id)->where('status', 'pending')->count(),
                'shortlisted' => DB::table('creator_project_bids')->where('project_id', $id)->where('status', 'shortlisted')->count(),
                'selected'    => DB::table('creator_project_bids')->where('project_id', $id)->where('status', 'selected')->count(),
                'rejected'    => DB::table('creator_project_bids')->where('project_id', $id)->where('status', 'rejected')->count(),
            ];

            return response()->json(['status' => true, 'message' => 'Project details loaded', 'data' => $project]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getMyProjectDetails: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Close project
    // ─────────────────────────────────────────────────────────────────────────

    public function closeProject(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $affected = DB::table('creator_projects')
                ->where('id', $id)
                ->where('firm_id', $firmProfile->id)
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->update(['status' => 'closed', 'closed_at' => now(), 'updated_at' => now()]);

            if (! $affected) {
                return response()->json(['status' => false, 'message' => 'Project not found or already closed'], 404);
            }

            return response()->json(['status' => true, 'message' => 'Project closed successfully']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@closeProject: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Get bids on a project
    // ─────────────────────────────────────────────────────────────────────────

    public function getProjectBids(Request $request, $projectId): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
        }

        $project = DB::table('creator_projects')
            ->where('id', $projectId)
            ->where('firm_id', $firmProfile->id)
            ->first();

        if (! $project) {
            return response()->json(['status' => false, 'message' => 'Project not found'], 404);
        }

        $query = DB::table('creator_project_bids')
            ->join('users', 'creator_project_bids.creator_id', '=', 'users.id')
            ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'creator_project_bids.creator_id')
            ->leftJoin('creator_engagements', 'creator_engagements.bid_id', '=', 'creator_project_bids.id')
            ->where('creator_project_bids.project_id', $projectId);

        if ($request->filled('status')) {
            $query->where('creator_project_bids.status', $request->status);
        }

        $bids = $query->orderByDesc('creator_project_bids.created_at')
            ->get([
                'creator_project_bids.id',
                'creator_project_bids.creator_id',
                'creator_project_bids.bid_amount',
                'creator_project_bids.delivery_days',
                'creator_project_bids.proposal',
                'creator_project_bids.portfolio_links',
                'creator_project_bids.status',
                'creator_project_bids.created_at',
                'users.name as creator_name',
                'users.email as creator_email',
                'users.profile_image as creator_avatar',
                'student_profiles.qualification as creator_qualification',
                'student_profiles.experience_years as creator_experience_years',
                'student_profiles.availability_status as creator_availability',
                'student_profiles.why_should_hire_you as creator_why_hire',
                'student_profiles.linkedin_url as creator_linkedin',
                'student_profiles.portfolio_url as creator_portfolio',
                'creator_engagements.id as engagement_id',
                'creator_engagements.status as engagement_status',
            ]);

        foreach ($bids as $bid) {
            $bid->portfolio_links = $bid->portfolio_links ? json_decode($bid->portfolio_links) : [];
        }

        return response()->json([
            'status'  => true,
            'message' => 'Bids loaded',
            'data'    => ['bids' => $bids, 'project' => $project],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getProjectBids: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Update bid status (shortlist / select / reject)
    // ─────────────────────────────────────────────────────────────────────────

    public function updateBidStatus(Request $request, $bidId): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:shortlisted,selected,rejected,pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $bid = DB::table('creator_project_bids')
            ->join('creator_projects', 'creator_project_bids.project_id', '=', 'creator_projects.id')
            ->where('creator_project_bids.id', $bidId)
            ->where('creator_projects.firm_id', $firmProfile->id)
            ->first(['creator_project_bids.id', 'creator_project_bids.status', 'creator_project_bids.project_id']);

        if (! $bid) {
            return response()->json(['status' => false, 'message' => 'Bid not found'], 404);
        }

        if ($bid->status === 'withdrawn') {
            return response()->json(['status' => false, 'message' => 'Cannot update a withdrawn bid'], 422);
        }

        DB::table('creator_project_bids')
            ->where('id', $bidId)
            ->update(['status' => $request->status, 'updated_at' => now()]);

        return response()->json([
            'status'  => true,
            'message' => 'Bid status updated to ' . $request->status,
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@updateBidStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Browse published projects
    // ─────────────────────────────────────────────────────────────────────────

    public function browseProjects(Request $request): JsonResponse
    {
        try {
        $query = DB::table('creator_projects')
            ->join('firm_profiles', 'creator_projects.firm_id', '=', 'firm_profiles.id')
            ->where('creator_projects.status', 'published');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('creator_projects.title', 'like', $s)
                  ->orWhere('creator_projects.description', 'like', $s)
                  ->orWhere('creator_projects.category', 'like', $s);
            });
        }

        if ($request->filled('category')) {
            $query->where('creator_projects.category', $request->category);
        }

        if ($request->filled('budget_min')) {
            $query->where(function ($q) use ($request) {
                $q->where('creator_projects.budget_min', '>=', $request->budget_min)
                  ->orWhere('creator_projects.budget_max', '>=', $request->budget_min);
            });
        }

        if ($request->filled('budget_max')) {
            $query->where(function ($q) use ($request) {
                $q->where('creator_projects.budget_max', '<=', $request->budget_max)
                  ->orWhere('creator_projects.budget_min', '<=', $request->budget_max);
            });
        }

        $projects = $query->orderByDesc('creator_projects.published_at')
            ->get([
                'creator_projects.id',
                'creator_projects.title',
                'creator_projects.slug',
                'creator_projects.description',
                'creator_projects.category',
                'creator_projects.budget_type',
                'creator_projects.budget_min',
                'creator_projects.budget_max',
                'creator_projects.delivery_days',
                'creator_projects.published_at',
                DB::raw("CASE WHEN firm_profiles.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
            ]);

        foreach ($projects as $p) {
            $p->bid_count = DB::table('creator_project_bids')
                ->where('project_id', $p->id)
                ->whereNotIn('status', ['withdrawn'])
                ->count();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Projects loaded',
            'data'    => ['projects' => $projects],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@browseProjects: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Public project details + user's own bid
    // ─────────────────────────────────────────────────────────────────────────

    public function publicProjectDetails(Request $request, $id): JsonResponse
    {
        try {
        $project = DB::table('creator_projects')
            ->join('firm_profiles', 'creator_projects.firm_id', '=', 'firm_profiles.id')
            ->where('creator_projects.id', $id)
            ->where('creator_projects.status', 'published')
            ->first([
                'creator_projects.id',
                'creator_projects.title',
                'creator_projects.description',
                'creator_projects.category',
                'creator_projects.budget_type',
                'creator_projects.budget_min',
                'creator_projects.budget_max',
                'creator_projects.delivery_days',
                'creator_projects.skills_required',
                'creator_projects.status',
                'creator_projects.published_at',
                DB::raw("CASE WHEN firm_profiles.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
            ]);

        if (! $project) {
            return response()->json(['status' => false, 'message' => 'Project not found'], 404);
        }

        $project->skills_required = $project->skills_required ? json_decode($project->skills_required) : [];
        $project->bid_count = DB::table('creator_project_bids')
            ->where('project_id', $id)
            ->whereNotIn('status', ['withdrawn'])
            ->count();

        $myBid = null;
        $user  = $request->attributes->get('auth_user');
        if ($user) {
            $myBid = DB::table('creator_project_bids')
                ->where('project_id', $id)
                ->where('creator_id', $user->id)
                ->first();
            if ($myBid) {
                $myBid->portfolio_links = $myBid->portfolio_links ? json_decode($myBid->portfolio_links) : [];
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Project details loaded',
            'data'    => ['project' => $project, 'my_bid' => $myBid],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@publicProjectDetails: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Submit bid
    // ─────────────────────────────────────────────────────────────────────────

    public function submitBid(Request $request, $projectId): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $project = DB::table('creator_projects')
            ->where('id', $projectId)
            ->where('status', 'published')
            ->first();

        if (! $project) {
            return response()->json(['status' => false, 'message' => 'Project not found or not open for bids'], 404);
        }

        $existing = DB::table('creator_project_bids')
            ->where('project_id', $projectId)
            ->where('creator_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['status' => false, 'message' => 'You have already submitted a bid for this project'], 422);
        }

        $validator = Validator::make($request->all(), [
            'bid_amount'      => 'required|numeric|min:0',
            'delivery_days'   => 'nullable|integer|min:1',
            'proposal'        => 'required|string|min:20',
            'portfolio_url'   => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $portfolioUrl = $request->portfolio_url;

        $id = DB::table('creator_project_bids')->insertGetId([
            'project_id'      => $projectId,
            'creator_id'      => $user->id,
            'bid_amount'      => $request->bid_amount,
            'delivery_days'   => $request->delivery_days ?? ($project->delivery_days ?? 1),
            'proposal'        => $request->proposal,
            'portfolio_links' => $portfolioUrl ? json_encode([$portfolioUrl]) : null,
            'status'          => 'pending',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Bid submitted successfully',
            'data'    => ['id' => $id],
        ], 201);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@submitBid: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Withdraw bid
    // ─────────────────────────────────────────────────────────────────────────

    public function withdrawBid(Request $request, $bidId): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $affected = DB::table('creator_project_bids')
            ->where('id', $bidId)
            ->where('creator_id', $user->id)
            ->whereIn('status', ['pending', 'shortlisted'])
            ->update(['status' => 'withdrawn', 'updated_at' => now()]);

        if (! $affected) {
            return response()->json(['status' => false, 'message' => 'Bid not found or cannot be withdrawn'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Bid withdrawn successfully']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@withdrawBid: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — My bids
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyBids(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $query = DB::table('creator_project_bids')
            ->join('creator_projects', 'creator_project_bids.project_id', '=', 'creator_projects.id')
            ->join('firm_profiles', 'creator_projects.firm_id', '=', 'firm_profiles.id')
            ->leftJoin('creator_engagements', 'creator_engagements.bid_id', '=', 'creator_project_bids.id')
            ->where('creator_project_bids.creator_id', $user->id);

        if ($request->filled('status')) {
            $query->where('creator_project_bids.status', $request->status);
        }

        $bids = $query->orderByDesc('creator_project_bids.created_at')
            ->get([
                'creator_project_bids.id',
                'creator_project_bids.bid_amount',
                'creator_project_bids.delivery_days',
                'creator_project_bids.proposal',
                'creator_project_bids.status',
                'creator_project_bids.created_at',
                'creator_projects.id as project_id',
                'creator_projects.title as project_title',
                'creator_projects.category',
                'creator_projects.status as project_status',
                'creator_projects.budget_type',
                'creator_projects.budget_min',
                'creator_projects.budget_max',
                DB::raw("CASE WHEN firm_profiles.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
                'creator_engagements.id as engagement_id',
            ]);

        return response()->json([
            'status'  => true,
            'message' => 'Bids loaded',
            'data'    => ['bids' => $bids],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getMyBids: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Accept creator (notifies creator to respond)
    // ─────────────────────────────────────────────────────────────────────────

    public function acceptCreator(Request $request, $bidId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $bid = DB::table('creator_project_bids as b')
                ->join('creator_projects as p', 'p.id', '=', 'b.project_id')
                ->where('b.id', $bidId)
                ->where('p.firm_id', $firmProfile->id)
                ->select(['b.id', 'b.status', 'b.creator_id', 'b.project_id', 'p.title as project_title'])
                ->first();

            if (! $bid) {
                return response()->json(['status' => false, 'message' => 'Bid not found'], 404);
            }

            if (in_array($bid->status, ['withdrawn', 'creator_declined'])) {
                return response()->json(['status' => false, 'message' => 'Cannot accept this bid'], 422);
            }

            $alreadySelected = $bid->status === 'selected';

            DB::beginTransaction();

            DB::table('creator_project_bids')
                ->where('id', $bidId)
                ->update(['status' => 'selected', 'updated_at' => now()]);

            if (! $alreadySelected) {
                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $bid->creator_id,
                    'type'       => 'bid_selected',
                    'title'      => "You've been selected!",
                    'body'       => "A firm selected you for \"{$bid->project_title}\". Review the contract and respond.",
                    'data'       => json_encode(['bid_id' => (int) $bidId, 'project_id' => (int) $bid->project_id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            if (! $alreadySelected) {
                $creator = DB::table('users')->where('id', $bid->creator_id)->first();
                if ($creator) {
                    (new EmailNotificationService())->sendCreatorSelected(
                        $creator->email,
                        $creator->name,
                        $bid->project_title,
                        (int) $bidId
                    );
                }
            }

            return response()->json([
                'status'  => true,
                'message' => $alreadySelected ? 'Creator already accepted' : 'Creator accepted. Notification sent.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@acceptCreator: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Selected bid details (for respond/contract preview page)
    // ─────────────────────────────────────────────────────────────────────────

    public function getSelectedBidDetails(Request $request, $bidId): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $bid = DB::table('creator_project_bids as b')
            ->join('creator_projects as p', 'p.id', '=', 'b.project_id')
            ->join('firm_profiles as fp', 'fp.id', '=', 'p.firm_id')
            ->where('b.id', $bidId)
            ->where('b.creator_id', $user->id)
            ->select([
                'b.id', 'b.bid_amount', 'b.delivery_days', 'b.proposal',
                'b.portfolio_links', 'b.status',
                'p.id as project_id', 'p.title', 'p.description', 'p.category',
                'p.skills_required', 'p.delivery_days as project_delivery_days',
                'p.budget_type', 'p.budget_min', 'p.budget_max',
                DB::raw("CASE WHEN fp.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
            ])
            ->first();

        if (! $bid) {
            return response()->json(['status' => false, 'message' => 'Bid not found'], 404);
        }

        $bid->skills_required = $bid->skills_required ? json_decode($bid->skills_required) : [];
        $bid->portfolio_links = $bid->portfolio_links ? json_decode($bid->portfolio_links) : [];

        $engagement = DB::table('creator_engagements')->where('bid_id', $bidId)->first();

        return response()->json([
            'status' => true,
            'data'   => ['bid' => $bid, 'engagement' => $engagement],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getSelectedBidDetails: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Respond to firm's acceptance (accept or decline)
    // ─────────────────────────────────────────────────────────────────────────

    public function creatorRespondToBid(Request $request, $bidId): JsonResponse
    {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:accept,decline',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Invalid action', 'errors' => $validator->errors()], 422);
        }

        $bid = DB::table('creator_project_bids as b')
            ->join('creator_projects as p', 'p.id', '=', 'b.project_id')
            ->where('b.id', $bidId)
            ->where('b.creator_id', $user->id)
            ->where('b.status', 'selected')
            ->select([
                'b.id', 'b.bid_amount', 'b.delivery_days',
                'b.project_id', 'p.title as project_title',
                'p.firm_id', 'p.delivery_days as project_delivery_days',
            ])
            ->first();

        if (! $bid) {
            return response()->json(['status' => false, 'message' => 'Bid not found or not awaiting your response'], 404);
        }

        $existing = DB::table('creator_engagements')->where('bid_id', $bidId)->first();
        if ($existing) {
            return response()->json([
                'status'  => false,
                'message' => 'You have already responded to this bid',
                'data'    => ['engagement_id' => $existing->id],
            ], 422);
        }

        $firmUserId = DB::table('firm_profiles')->where('id', $bid->firm_id)->value('user_id');

        try {
            DB::beginTransaction();

            if ($request->action === 'accept') {
                $engagementId = DB::table('creator_engagements')->insertGetId([
                    'creator_requirement_id' => $bid->project_id,
                    'bid_id'                 => $bid->id,
                    'creator_id'             => $user->id,
                    'firm_id'                => $bid->firm_id,
                    'accepted_bid_amount'    => $bid->bid_amount,
                    'delivery_days'          => $bid->delivery_days ?? $bid->project_delivery_days ?? 7,
                    'status'                 => 'awaiting_payment',
                    'creator_accepted_at'    => now(),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                if ($firmUserId) {
                    DB::table('creator_marketplace_notifications')->insert([
                        'user_id'    => $firmUserId,
                        'type'       => 'creator_accepted',
                        'title'      => 'Creator accepted your project!',
                        'body'       => "A creator accepted \"{$bid->project_title}\". Review the contract.",
                        'data'       => json_encode(['engagement_id' => $engagementId, 'bid_id' => (int) $bidId]),
                        'read_at'    => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::commit();

                if ($firmUserId) {
                    $firmUser = DB::table('users')->where('id', $firmUserId)->first();
                    if ($firmUser) {
                        (new EmailNotificationService())->sendCreatorAccepted(
                            $firmUser->email,
                            $firmUser->name,
                            $user->name,
                            $bid->project_title,
                            $engagementId
                        );
                    }
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Project accepted! Contract created.',
                    'data'    => ['engagement_id' => $engagementId],
                ]);
            }

            // decline
            DB::table('creator_project_bids')
                ->where('id', $bidId)
                ->update(['status' => 'creator_declined', 'updated_at' => now()]);

            if ($firmUserId) {
                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $firmUserId,
                    'type'       => 'creator_declined',
                    'title'      => 'Creator declined the project',
                    'body'       => "A creator declined \"{$bid->project_title}\".",
                    'data'       => json_encode(['bid_id' => (int) $bidId, 'project_id' => (int) $bid->project_id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Project declined.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@creatorRespondToBid: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED — Engagement contract (both firm and creator)
    // ─────────────────────────────────────────────────────────────────────────

    public function getEngagement(Request $request, $id): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');

        $engagement = DB::table('creator_engagements as e')
            ->join('creator_projects as p',       'p.id',  '=', 'e.creator_requirement_id')
            ->join('creator_project_bids as b',   'b.id',  '=', 'e.bid_id')
            ->join('firm_profiles as fp',          'fp.id', '=', 'e.firm_id')
            ->join('users as cu',                  'cu.id', '=', 'e.creator_id')
            ->where('e.id', $id)
            ->select([
                'e.id', 'e.status', 'e.accepted_bid_amount', 'e.delivery_days',
                'e.creator_accepted_at', 'e.created_at', 'e.updated_at',
                'e.creator_requirement_id', 'e.bid_id', 'e.creator_id', 'e.firm_id',
                'p.title as project_title', 'p.description', 'p.category', 'p.skills_required',
                'b.proposal', 'b.portfolio_links',
                'fp.firm_name',
                DB::raw("CASE WHEN fp.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
                'cu.name as creator_name',
                'cu.email as creator_email',
            ])
            ->first();

        if (! $engagement) {
            return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
        }

        $firmUserId = DB::table('firm_profiles')->where('id', $engagement->firm_id)->value('user_id');
        $isFirm     = ($user->id === (int) $firmUserId);
        $isCreator  = ($user->id === (int) $engagement->creator_id);

        if (! $isFirm && ! $isCreator) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $engagement->skills_required = $engagement->skills_required ? json_decode($engagement->skills_required) : [];
        $engagement->portfolio_links = $engagement->portfolio_links ? json_decode($engagement->portfolio_links) : [];

        if (! $isFirm) {
            unset($engagement->firm_name);
        }

        // Attach payment summary visible to both parties
        $payment = DB::table('creator_engagement_payments')
            ->where('engagement_id', $id)
            ->first();

        $paymentData = null;
        if ($payment) {
            $paymentData = [
                'id'             => $payment->id,
                'status'         => $payment->status,
                'payment_method' => $payment->payment_method,
                'amount'         => (float) $payment->amount,
                'created_at'     => $payment->created_at,
            ];
            if ($isFirm) {
                $paymentData['utr_number']        = $payment->utr_number;
                $paymentData['payment_reference'] = $payment->payment_reference;
                $paymentData['payment_date']      = $payment->payment_date;
                $paymentData['admin_remarks']      = $payment->admin_remarks;
                $paymentData['reviewed_at']        = $payment->reviewed_at;
            }
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'engagement'   => $engagement,
                'viewer_role'  => $isFirm ? 'firm' : 'creator',
                'payment'      => $paymentData,
            ],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getEngagement: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — My engagements
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyEngagements(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $engagements = DB::table('creator_engagements as e')
            ->join('creator_projects as p', 'p.id', '=', 'e.creator_requirement_id')
            ->join('firm_profiles as fp',   'fp.id', '=', 'e.firm_id')
            ->where('e.creator_id', $user->id)
            ->select([
                'e.id', 'e.status', 'e.accepted_bid_amount', 'e.delivery_days',
                'e.creator_accepted_at', 'e.created_at',
                'p.title as project_title', 'p.category', 'p.id as project_id',
                DB::raw("CASE WHEN fp.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
            ])
            ->orderByDesc('e.created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => ['engagements' => $engagements],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getMyEngagements: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — My engagements
    // ─────────────────────────────────────────────────────────────────────────

    public function getFirmEngagements(Request $request): JsonResponse
    {
        try {
        $user        = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
        }

        $engagements = DB::table('creator_engagements as e')
            ->join('creator_projects as p', 'p.id', '=', 'e.creator_requirement_id')
            ->join('users as cu',           'cu.id', '=', 'e.creator_id')
            ->where('e.firm_id', $firmProfile->id)
            ->select([
                'e.id', 'e.status', 'e.accepted_bid_amount', 'e.delivery_days',
                'e.creator_accepted_at', 'e.created_at',
                'p.title as project_title', 'p.category', 'p.id as project_id',
                'cu.name as creator_name',
            ])
            ->orderByDesc('e.created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => ['engagements' => $engagements],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getFirmEngagements: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED — Notifications
    // ─────────────────────────────────────────────────────────────────────────

    public function getMarketplaceNotifications(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonMarketplaceUser($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $notifications = DB::table('creator_marketplace_notifications')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        foreach ($notifications as $n) {
            $n->data = $n->data ? json_decode($n->data) : null;
        }

        $unreadCount = DB::table('creator_marketplace_notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status' => true,
            'data'   => ['notifications' => $notifications, 'unread_count' => $unreadCount],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getMarketplaceNotifications: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function markNotificationRead(Request $request, $id): JsonResponse
    {
        try {
        if ($err = $this->forbidNonMarketplaceUser($request)) return $err;
        $user = $request->attributes->get('auth_user');

        DB::table('creator_marketplace_notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@markNotificationRead: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonMarketplaceUser($request)) return $err;
        $user = $request->attributes->get('auth_user');

        DB::table('creator_marketplace_notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@markAllNotificationsRead: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Initiate Razorpay payment for an engagement
    // ─────────────────────────────────────────────────────────────────────────

    public function initiatePayment(Request $request, $engagementId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $engagement = DB::table('creator_engagements')
                ->where('id', $engagementId)
                ->where('firm_id', $firmProfile->id)
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            if ($engagement->status !== 'awaiting_payment') {
                return response()->json(['status' => false, 'message' => 'Payment not required for this engagement'], 422);
            }

            // Cancel any stale pending Razorpay orders before creating a new one
            DB::beginTransaction();

            DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->where('payment_method', 'razorpay')
                ->where('status', 'pending')
                ->delete();

            // Block if a manual payment is already under review or verified
            $blocking = DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->whereIn('status', ['awaiting_verification', 'verified', 'escrow_held'])
                ->first();

            if ($blocking) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'A payment is already in progress'], 422);
            }

            $gateway = PaymentGatewayFactory::make('razorpay');
            $receipt = 'eng_' . $engagementId . '_firm_' . $firmProfile->id . '_' . time();

            $order = $gateway->createOrder((float) $engagement->accepted_bid_amount, $receipt, [
                'engagement_id' => (string) $engagementId,
            ]);

            $paymentId = DB::table('creator_engagement_payments')->insertGetId([
                'engagement_id'    => $engagementId,
                'firm_id'          => $firmProfile->id,
                'amount'           => $engagement->accepted_bid_amount,
                'currency'         => 'INR',
                'payment_method'   => 'razorpay',
                'status'           => 'pending',
                'gateway_order_id' => $order['order_id'],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'data'   => [
                    'payment_id'   => $paymentId,
                    'order_id'     => $order['order_id'],
                    'amount'       => $order['amount'],
                    'currency'     => $order['currency'],
                    'key'          => config('services.razorpay.key'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@initiatePayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not create payment order'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Verify Razorpay payment signature and activate engagement
    // ─────────────────────────────────────────────────────────────────────────

    public function verifyEngagementPayment(Request $request, $engagementId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $validator = Validator::make($request->all(), [
                'razorpay_order_id'   => 'required|string',
                'razorpay_payment_id' => 'required|string',
                'razorpay_signature'  => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $payment = DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->where('firm_id', $firmProfile->id)
                ->where('gateway_order_id', $request->razorpay_order_id)
                ->first();

            if (! $payment) {
                return response()->json(['status' => false, 'message' => 'Payment record not found'], 404);
            }

            if ($payment->status === 'escrow_held') {
                return response()->json(['status' => true, 'message' => 'Payment already processed']);
            }

            $gateway = PaymentGatewayFactory::make('razorpay');

            if (! $gateway->verifySignature([
                'order_id'   => $request->razorpay_order_id,
                'payment_id' => $request->razorpay_payment_id,
                'signature'  => $request->razorpay_signature,
            ])) {
                return response()->json(['status' => false, 'message' => 'Payment verification failed'], 422);
            }

            $paymentDetails = $gateway->fetchPayment($request->razorpay_payment_id);

            DB::beginTransaction();

            DB::table('creator_engagement_payments')
                ->where('id', $payment->id)
                ->update([
                    'status'             => 'escrow_held',
                    'gateway_payment_id' => $request->razorpay_payment_id,
                    'gateway_signature'  => $request->razorpay_signature,
                    'gateway_response'   => json_encode($paymentDetails),
                    'updated_at'         => now(),
                ]);

            DB::table('creator_engagements')
                ->where('id', $engagementId)
                ->update(['status' => 'active', 'updated_at' => now()]);

            // Notify creator that project is now active
            $engagement = DB::table('creator_engagements')->where('id', $engagementId)->first();
            $project    = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();

            DB::table('creator_marketplace_notifications')->insert([
                'user_id'    => $engagement->creator_id,
                'type'       => 'payment_received',
                'title'      => 'Payment received — project is now active!',
                'body'       => "The firm has paid for \"{$project->title}\". Your project is now active.",
                'data'       => json_encode(['engagement_id' => (int) $engagementId]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Payment verified. Project is now active!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@verifyEngagementPayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Verification error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Handle Razorpay checkout failure
    // ─────────────────────────────────────────────────────────────────────────

    public function engagementPaymentFailure(Request $request, $engagementId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            // Delete the stale pending record so the firm can try again
            DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->where('firm_id', $firmProfile->id)
                ->where('payment_method', 'razorpay')
                ->where('status', 'pending')
                ->delete();

            return response()->json(['status' => true, 'message' => 'Payment attempt logged.']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@engagementPaymentFailure: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Submit manual payment proof
    // ─────────────────────────────────────────────────────────────────────────

    public function submitManualPayment(Request $request, $engagementId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $engagement = DB::table('creator_engagements')
                ->where('id', $engagementId)
                ->where('firm_id', $firmProfile->id)
                ->whereIn('status', ['awaiting_payment', 'payment_pending'])
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found or payment not applicable'], 404);
            }

            $validator = Validator::make($request->all(), [
                'utr_number'        => 'nullable|string|max:100',
                'payment_reference' => 'required|string|max:255',
                'payment_date'      => 'required|date',
                'screenshot'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $screenshotUrl = null;
            if ($request->hasFile('screenshot')) {
                $file          = $request->file('screenshot');
                $screenshotUrl = $file->storeAs(
                    'creator_payments',
                    'eng_' . $engagementId . '_' . time() . '.' . $file->getClientOriginalExtension(),
                    'public'
                );
            }

            // Upsert: replace any previous pending/awaiting_verification record
            $existing = DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->whereIn('status', ['pending', 'awaiting_verification'])
                ->first();

            DB::beginTransaction();

            if ($existing) {
                DB::table('creator_engagement_payments')
                    ->where('id', $existing->id)
                    ->update([
                        'payment_method'    => 'manual',
                        'status'            => 'awaiting_verification',
                        'utr_number'        => $request->utr_number,
                        'payment_reference' => $request->payment_reference,
                        'screenshot_url'    => $screenshotUrl ?? $existing->screenshot_url,
                        'payment_date'      => $request->payment_date,
                        'admin_remarks'     => null,
                        'reviewed_by'       => null,
                        'reviewed_at'       => null,
                        'updated_at'        => now(),
                    ]);
            } else {
                DB::table('creator_engagement_payments')->insertGetId([
                    'engagement_id'     => $engagementId,
                    'firm_id'           => $firmProfile->id,
                    'amount'            => $engagement->accepted_bid_amount,
                    'currency'          => 'INR',
                    'payment_method'    => 'manual',
                    'status'            => 'awaiting_verification',
                    'utr_number'        => $request->utr_number,
                    'payment_reference' => $request->payment_reference,
                    'screenshot_url'    => $screenshotUrl,
                    'payment_date'      => $request->payment_date,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            DB::table('creator_engagements')
                ->where('id', $engagementId)
                ->update(['status' => 'payment_pending', 'updated_at' => now()]);

            DB::commit();
            return response()->json([
                'status'  => true,
                'message' => 'Payment proof submitted. Admin will review within 24 hours.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@submitManualPayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED — Get payment details for an engagement
    // ─────────────────────────────────────────────────────────────────────────

    public function getEngagementPayment(Request $request, $engagementId): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');

        $engagement = DB::table('creator_engagements')->where('id', $engagementId)->first();

        if (! $engagement) {
            return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
        }

        $firmUserId = DB::table('firm_profiles')->where('id', $engagement->firm_id)->value('user_id');
        $isFirm     = ($user->id === (int) $firmUserId);
        $isCreator  = ($user->id === (int) $engagement->creator_id);

        if (! $isFirm && ! $isCreator) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = DB::table('creator_engagement_payments')
            ->where('engagement_id', $engagementId)
            ->first();

        if (! $payment) {
            return response()->json(['status' => true, 'data' => ['payment' => null]]);
        }

        $data = [
            'id'             => $payment->id,
            'status'         => $payment->status,
            'payment_method' => $payment->payment_method,
            'amount'         => (float) $payment->amount,
            'currency'       => $payment->currency,
            'created_at'     => $payment->created_at,
            'updated_at'     => $payment->updated_at,
        ];

        if ($isFirm) {
            $data['utr_number']        = $payment->utr_number;
            $data['payment_reference'] = $payment->payment_reference;
            $data['screenshot_url']    = $payment->screenshot_url
                ? asset('storage/' . $payment->screenshot_url)
                : null;
            $data['payment_date']      = $payment->payment_date;
            $data['admin_remarks']     = $payment->admin_remarks;
            $data['reviewed_at']       = $payment->reviewed_at;
        }

        return response()->json(['status' => true, 'data' => ['payment' => $data]]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getEngagementPayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WORKSPACE — Full workspace data (brief, submissions, timeline)
    // ─────────────────────────────────────────────────────────────────────────

    public function getWorkspace(Request $request, $id): JsonResponse
    {
        try {
        $user = $request->attributes->get('auth_user');

        $engagement = DB::table('creator_engagements')->where('id', $id)->first();

        if (! $engagement) {
            return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
        }

        $firmUserId = DB::table('firm_profiles')->where('id', $engagement->firm_id)->value('user_id');
        $isFirm     = ($user->id === (int) $firmUserId);
        $isCreator  = ($user->id === (int) $engagement->creator_id);

        if (! $isFirm && ! $isCreator) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $workspaceStatuses = ['active', 'submitted', 'revision_requested', 'resubmitted', 'approved', 'payout_pending', 'completed'];
        if (! in_array($engagement->status, $workspaceStatuses)) {
            return response()->json(['status' => false, 'message' => 'Workspace not available yet'], 422);
        }

        // Brief
        $brief     = DB::table('engagement_briefs')->where('engagement_id', $id)->first();
        $briefData = null;
        if ($brief) {
            $attachments = DB::table('engagement_brief_attachments')
                ->where('engagement_id', $id)
                ->orderBy('created_at')
                ->get()
                ->map(function ($a) {
                    $a->url = asset('storage/' . $a->file_path);
                    return $a;
                });

            $briefData = [
                'id'               => $brief->id,
                'detailed_brief'   => $brief->detailed_brief,
                'additional_notes' => $brief->additional_notes,
                'attachments'      => $attachments,
                'updated_at'       => $brief->updated_at,
            ];
        }

        // Submissions (all rounds, oldest first)
        $submissions = DB::table('engagement_submissions')
            ->where('engagement_id', $id)
            ->orderBy('revision_round')
            ->get();

        foreach ($submissions as $sub) {
            $sub->files = DB::table('engagement_submission_files')
                ->where('submission_id', $sub->id)
                ->get()
                ->map(function ($f) {
                    $f->url = $f->file_path ? asset('storage/' . $f->file_path) : null;
                    return $f;
                });
        }

        // Timeline (newest first, capped at 100)
        $timeline = DB::table('engagement_timeline as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('t.engagement_id', $id)
            ->orderByDesc('t.created_at')
            ->limit(100)
            ->select(['t.*', 'u.name as actor_name'])
            ->get()
            ->map(function ($t) {
                $t->meta = $t->meta ? json_decode($t->meta) : null;
                return $t;
            });

        $project = DB::table('creator_projects')
            ->where('id', $engagement->creator_requirement_id)
            ->select(['title', 'category'])
            ->first();

        return response()->json([
            'status' => true,
            'data'   => [
                'engagement'  => [
                    'id'                  => (int) $engagement->id,
                    'status'              => $engagement->status,
                    'accepted_bid_amount' => (float) $engagement->accepted_bid_amount,
                    'delivery_days'       => (int) $engagement->delivery_days,
                    'creator_id'          => (int) $engagement->creator_id,
                    'firm_id'             => (int) $engagement->firm_id,
                    'project_title'       => $project?->title,
                    'category'            => $project?->category,
                ],
                'viewer_role' => $isFirm ? 'firm' : 'creator',
                'brief'       => $briefData,
                'submissions' => $submissions,
                'timeline'    => $timeline,
            ],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getWorkspace: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Save / update project brief (upsert + append new attachments)
    // ─────────────────────────────────────────────────────────────────────────

    public function saveBrief(Request $request, $id): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $engagement = DB::table('creator_engagements')
                ->where('id', $id)
                ->where('firm_id', $firmProfile->id)
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            $allowed = ['active', 'submitted', 'revision_requested', 'resubmitted', 'approved'];
            if (! in_array($engagement->status, $allowed)) {
                return response()->json(['status' => false, 'message' => 'Cannot edit brief at this stage'], 422);
            }

            $validator = Validator::make($request->all(), [
                'detailed_brief'   => 'required|string|max:10000',
                'additional_notes' => 'nullable|string|max:5000',
                'attachments'      => 'nullable|array|max:10',
                'attachments.*'    => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,txt|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $existing = DB::table('engagement_briefs')->where('engagement_id', $id)->first();
            $isNew    = ! $existing;

            DB::beginTransaction();

            if ($existing) {
                DB::table('engagement_briefs')->where('id', $existing->id)->update([
                    'detailed_brief'   => $request->detailed_brief,
                    'additional_notes' => $request->additional_notes,
                    'updated_by'       => $user->id,
                    'updated_at'       => now(),
                ]);
            } else {
                DB::table('engagement_briefs')->insert([
                    'engagement_id'    => $id,
                    'detailed_brief'   => $request->detailed_brief,
                    'additional_notes' => $request->additional_notes,
                    'updated_by'       => $user->id,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Append any newly uploaded files
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->storeAs(
                        'workspace_briefs',
                        'eng_' . $id . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension(),
                        'public'
                    );
                    DB::table('engagement_brief_attachments')->insert([
                        'engagement_id' => $id,
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'     => $file->getMimeType(),
                        'file_size'     => $file->getSize(),
                        'uploaded_by'   => $user->id,
                        'created_at'    => now(),
                    ]);
                }
            }

            $this->addTimeline($id, $user->id, 'firm', $isNew ? 'brief_published' : 'brief_updated');

            $project = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();

            if ($isNew) {
                $this->notify(
                    $engagement->creator_id,
                    'brief_shared',
                    'Project brief is ready',
                    "The firm shared a brief for \"{$project->title}\".",
                    ['engagement_id' => (int) $id]
                );
            } else {
                $this->notify(
                    $engagement->creator_id,
                    'brief_updated',
                    'Project brief updated',
                    "The firm updated the project brief for \"{$project->title}\". Check the Brief tab for changes.",
                    ['engagement_id' => (int) $id]
                );
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => $isNew ? 'Brief published.' : 'Brief updated.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@saveBrief: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Delete a single brief attachment
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteBriefAttachment(Request $request, $id, $attachmentId): JsonResponse
    {
        try {
        $user        = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify firm owns this engagement
        $owns = DB::table('creator_engagements')
            ->where('id', $id)
            ->where('firm_id', $firmProfile->id)
            ->exists();

        if (! $owns) {
            return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
        }

        $attachment = DB::table('engagement_brief_attachments')
            ->where('id', $attachmentId)
            ->where('engagement_id', $id)
            ->first();

        if (! $attachment) {
            return response()->json(['status' => false, 'message' => 'Attachment not found'], 404);
        }

        Storage::disk('public')->delete($attachment->file_path);
        DB::table('engagement_brief_attachments')->where('id', $attachmentId)->delete();

        return response()->json(['status' => true, 'message' => 'Attachment deleted.']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@deleteBriefAttachment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Submit deliverable (new round each call)
    // ─────────────────────────────────────────────────────────────────────────

    public function submitDeliverable(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');

            $engagement = DB::table('creator_engagements')
                ->where('id', $id)
                ->where('creator_id', $user->id)
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            if (! in_array($engagement->status, ['active', 'revision_requested'])) {
                return response()->json(['status' => false, 'message' => 'Cannot submit work at this stage'], 422);
            }

            $validator = Validator::make($request->all(), [
                'notes'         => 'nullable|string|max:5000',
                'files'         => 'nullable|array|max:10',
                'files.*'       => 'file|mimes:pdf,doc,docx,zip,jpg,jpeg,png,gif,webp|max:51200',
                'video_links'   => 'nullable|array|max:10',
                'video_links.*' => 'url|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $hasFiles  = $request->hasFile('files') && count($request->file('files')) > 0;
            $hasVideos = ! empty(array_filter($request->input('video_links', [])));

            if (! $hasFiles && ! $hasVideos) {
                return response()->json(['status' => false, 'message' => 'Upload at least one file or add a video link'], 422);
            }

            $isRevision   = $engagement->status === 'revision_requested';
            $round        = DB::table('engagement_submissions')->where('engagement_id', $id)->count() + 1;

            DB::beginTransaction();

            $submissionId = DB::table('engagement_submissions')->insertGetId([
                'engagement_id'  => $id,
                'creator_id'     => $user->id,
                'notes'          => $request->notes,
                'revision_round' => $round,
                'status'         => 'submitted',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Physical files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->storeAs(
                        'workspace_deliverables',
                        'eng_' . $id . '_sub_' . $submissionId . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension(),
                        'public'
                    );
                    DB::table('engagement_submission_files')->insert([
                        'submission_id' => $submissionId,
                        'engagement_id' => $id,
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'     => $file->getMimeType(),
                        'file_size'     => $file->getSize(),
                        'video_link'    => null,
                        'created_at'    => now(),
                    ]);
                }
            }

            // Video links
            foreach ($request->input('video_links', []) as $link) {
                if (! filter_var($link, FILTER_VALIDATE_URL)) {
                    continue;
                }
                DB::table('engagement_submission_files')->insert([
                    'submission_id' => $submissionId,
                    'engagement_id' => $id,
                    'file_path'     => null,
                    'original_name' => null,
                    'mime_type'     => null,
                    'file_size'     => null,
                    'video_link'    => $link,
                    'created_at'    => now(),
                ]);
            }

            $newStatus = $isRevision ? 'resubmitted' : 'submitted';
            DB::table('creator_engagements')->where('id', $id)->update([
                'status'     => $newStatus,
                'updated_at' => now(),
            ]);

            $this->addTimeline(
                $id, $user->id, 'creator',
                $isRevision ? 'work_resubmitted' : 'work_submitted',
                $request->notes,
                ['round' => $round]
            );

            // Notify firm
            $firmProfile = DB::table('firm_profiles')->where('id', $engagement->firm_id)->first();
            $project     = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();

            if ($firmProfile) {
                $this->notify(
                    $firmProfile->user_id,
                    'work_submitted',
                    $isRevision ? 'Creator resubmitted work' : 'Creator submitted work',
                    $isRevision
                        ? "Work resubmitted (round {$round}) for \"{$project->title}\". Please review."
                        : "Work submitted for \"{$project->title}\". Please review and approve.",
                    ['engagement_id' => (int) $id]
                );
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Work submitted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@submitDeliverable: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Request revision on the latest submission
    // ─────────────────────────────────────────────────────────────────────────

    public function requestRevision(Request $request, $id): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $engagement = DB::table('creator_engagements')
                ->where('id', $id)
                ->where('firm_id', $firmProfile->id)
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            if (! in_array($engagement->status, ['submitted', 'resubmitted'])) {
                return response()->json(['status' => false, 'message' => 'No submitted work to review'], 422);
            }

            $validator = Validator::make($request->all(), [
                'revision_notes' => 'required|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $latest = DB::table('engagement_submissions')
                ->where('engagement_id', $id)
                ->orderByDesc('revision_round')
                ->first();

            DB::beginTransaction();

            if ($latest) {
                DB::table('engagement_submissions')->where('id', $latest->id)->update([
                    'status'         => 'revision_requested',
                    'revision_notes' => $request->revision_notes,
                    'reviewed_by'    => $user->id,
                    'reviewed_at'    => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::table('creator_engagements')->where('id', $id)->update([
                'status'     => 'revision_requested',
                'updated_at' => now(),
            ]);

            $this->addTimeline($id, $user->id, 'firm', 'revision_requested', $request->revision_notes);

            $project = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();
            $this->notify(
                $engagement->creator_id,
                'revision_requested',
                'Revision requested',
                "The firm requested changes on \"{$project->title}\".",
                ['engagement_id' => (int) $id]
            );

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Revision requested.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@requestRevision: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Approve the latest submission
    // ─────────────────────────────────────────────────────────────────────────

    public function approveDeliverable(Request $request, $id): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $engagement = DB::table('creator_engagements')
                ->where('id', $id)
                ->where('firm_id', $firmProfile->id)
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            if (! in_array($engagement->status, ['submitted', 'resubmitted'])) {
                return response()->json(['status' => false, 'message' => 'No submitted work to approve'], 422);
            }

            $latest = DB::table('engagement_submissions')
                ->where('engagement_id', $id)
                ->orderByDesc('revision_round')
                ->first();

            $project        = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();
            $commissionRate = (float) (DB::table('platform_settings')->where('key', 'commission_percentage')->value('value') ?? '10');
            $gross          = (float) $engagement->accepted_bid_amount;
            $commission     = round($gross * $commissionRate / 100, 2);
            $net            = round($gross - $commission, 2);

            DB::beginTransaction();

            if ($latest) {
                DB::table('engagement_submissions')->where('id', $latest->id)->update([
                    'status'      => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Create payout record (idempotent — cron is safety net)
            $payoutExists = DB::table('creator_payouts')->where('engagement_id', $id)->exists();
            if (! $payoutExists) {
                DB::table('creator_payouts')->insert([
                    'engagement_id'     => $id,
                    'creator_id'        => $engagement->creator_id,
                    'gross_amount'      => $gross,
                    'commission_rate'   => $commissionRate,
                    'commission_amount' => $commission,
                    'net_amount'        => $net,
                    'status'            => 'pending',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            DB::table('creator_engagements')->where('id', $id)->update([
                'status'     => 'payout_pending',
                'updated_at' => now(),
            ]);

            $this->addTimeline($id, $user->id, 'firm', 'work_approved');
            $this->addTimeline($id, null, 'system', 'payout_queued', null, ['commission_rate' => $commissionRate]);

            $this->notify(
                $engagement->creator_id,
                'work_approved',
                'Your work has been approved!',
                "The firm approved your deliverable for \"{$project->title}\". Your payout of ₹" . number_format($net, 2) . " has been queued.",
                ['engagement_id' => (int) $id]
            );

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Deliverable approved. Payout queued for creator.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreatorMarketplace@approveDeliverable: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Bank details for payout
    // ─────────────────────────────────────────────────────────────────────────

    public function getBankDetails(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user    = $request->attributes->get('auth_user');
        $details = DB::table('creator_bank_details')
            ->where('creator_id', $user->id)
            ->first();

        if (! $details) {
            return response()->json(['status' => true, 'data' => ['bank_details' => null]]);
        }

        $accountMasked = '';
        try {
            $decrypted     = Crypt::decryptString($details->account_number);
            $accountMasked = '••••' . substr($decrypted, -4);
        } catch (\Exception $e) {
            $accountMasked = '••••' . substr($details->account_number, -4);
        }

        $ifsc = '';
        try {
            $ifsc = Crypt::decryptString($details->ifsc_code);
        } catch (\Exception $e) {
            $ifsc = $details->ifsc_code;
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'bank_details' => [
                    'id'                  => $details->id,
                    'account_holder_name' => $details->account_holder_name,
                    'bank_name'           => $details->bank_name,
                    'account_number'      => $accountMasked,
                    'ifsc_code'           => $ifsc,
                    'is_verified'         => (bool) $details->is_verified,
                    'updated_at'          => $details->updated_at,
                ],
            ],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getBankDetails: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function saveBankDetails(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $validator = Validator::make($request->all(), [
            'account_holder_name' => 'required|string|max:255',
            'bank_name'           => 'required|string|max:255',
            'account_number'      => 'required|string|min:9|max:20',
            'ifsc_code'           => ['required', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $encryptedAccount = Crypt::encryptString($request->account_number);
        $encryptedIfsc    = Crypt::encryptString(strtoupper($request->ifsc_code));

        $existing = DB::table('creator_bank_details')
            ->where('creator_id', $user->id)
            ->first();

        if ($existing) {
            DB::table('creator_bank_details')->where('id', $existing->id)->update([
                'account_holder_name' => $request->account_holder_name,
                'bank_name'           => $request->bank_name,
                'account_number'      => $encryptedAccount,
                'ifsc_code'           => $encryptedIfsc,
                'is_verified'         => 0,
                'updated_at'          => now(),
            ]);
        } else {
            DB::table('creator_bank_details')->insert([
                'creator_id'          => $user->id,
                'account_holder_name' => $request->account_holder_name,
                'bank_name'           => $request->bank_name,
                'account_number'      => $encryptedAccount,
                'ifsc_code'           => $encryptedIfsc,
                'is_verified'         => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Bank details saved.']);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@saveBankDetails: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getPayoutStatus(Request $request, $engagementId): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $engagement = DB::table('creator_engagements')
            ->where('id', $engagementId)
            ->where('creator_id', $user->id)
            ->first();

        if (! $engagement) {
            return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
        }

        $payout = DB::table('creator_payouts')
            ->where('engagement_id', $engagementId)
            ->first();

        $hasBankDetails = DB::table('creator_bank_details')
            ->where('creator_id', $user->id)
            ->exists();

        return response()->json([
            'status' => true,
            'data'   => [
                'payout'           => $payout ? [
                    'id'             => $payout->id,
                    'gross_amount'   => (float) $payout->gross_amount,
                    'commission_rate'   => (float) $payout->commission_rate,
                    'commission_amount' => (float) $payout->commission_amount,
                    'net_amount'     => (float) $payout->net_amount,
                    'status'         => $payout->status,
                    'paid_at'        => $payout->paid_at,
                    'created_at'     => $payout->created_at,
                ] : null,
                'has_bank_details' => $hasBankDetails,
            ],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getPayoutStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Bid detail (conditional company reveal)
    // ─────────────────────────────────────────────────────────────────────────

    public function getBidDetail(Request $request, $bidId): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $bid = DB::table('creator_project_bids as b')
            ->join('creator_projects as p',  'p.id',  '=', 'b.project_id')
            ->join('firm_profiles as fp',     'fp.id', '=', 'p.firm_id')
            ->leftJoin('creator_engagements as e', 'e.bid_id', '=', 'b.id')
            ->where('b.id', $bidId)
            ->where('b.creator_id', $user->id)
            ->first([
                'b.id',
                'b.bid_amount',
                'b.delivery_days',
                'b.proposal',
                'b.status',
                'b.created_at',
                'b.portfolio_links',
                'p.id as project_id',
                'p.title as project_title',
                'p.description as project_description',
                'p.category',
                'p.budget_type',
                'p.budget_min',
                'p.budget_max',
                'p.skills_required',
                'p.delivery_days as project_delivery_days',
                'p.status as project_status',
                'p.published_at',
                DB::raw("CASE WHEN fp.verification_status = 'approved' THEN 1 ELSE 0 END as firm_verified"),
                'e.id as engagement_id',
                'e.status as engagement_status',
                DB::raw("CASE WHEN e.id IS NOT NULL THEN fp.firm_name ELSE NULL END as company_name"),
            ]);

        if (! $bid) {
            return response()->json(['status' => false, 'message' => 'Bid not found'], 404);
        }

        $bid->portfolio_links = $bid->portfolio_links ? json_decode($bid->portfolio_links) : [];
        $bid->skills_required = $bid->skills_required ? json_decode($bid->skills_required) : [];
        $bid->bid_count = DB::table('creator_project_bids')
            ->where('project_id', $bid->project_id)
            ->whereNotIn('status', ['withdrawn'])
            ->count();

        return response()->json([
            'status' => true,
            'data'   => ['bid' => $bid],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getBidDetail: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Earnings summary
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyEarnings(Request $request): JsonResponse
    {
        try {
        if ($err = $this->forbidNonCreator($request)) return $err;
        $user = $request->attributes->get('auth_user');

        $released = (float) DB::table('creator_payouts as cp')
            ->join('creator_engagements as ce', 'ce.id', '=', 'cp.engagement_id')
            ->where('ce.creator_id', $user->id)
            ->where('cp.status', 'paid')
            ->sum('cp.net_amount');

        $pending = (float) DB::table('creator_payouts as cp')
            ->join('creator_engagements as ce', 'ce.id', '=', 'cp.engagement_id')
            ->where('ce.creator_id', $user->id)
            ->where('cp.status', 'pending')
            ->sum('cp.net_amount');

        $completedCount = DB::table('creator_engagements')
            ->where('creator_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $activeCount = DB::table('creator_engagements')
            ->where('creator_id', $user->id)
            ->whereIn('status', ['active', 'submitted', 'revision_requested', 'approved', 'payout_pending'])
            ->count();

        return response()->json([
            'status' => true,
            'data'   => [
                'total_earnings'    => $released + $pending,
                'released_earnings' => $released,
                'pending_earnings'  => $pending,
                'completed_projects'=> $completedCount,
                'active_projects'   => $activeCount,
            ],
        ]);
        } catch (\Exception $e) {
            Log::error('CreatorMarketplace@getMyEarnings: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function forbidNonCreator(Request $request): ?JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (! $user || $user->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }
        $profile = DB::table('student_profiles')
            ->where('user_id', $user->id)
            ->value('looking_for');
        if ($profile !== 'creator') {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }
        return null;
    }

    /**
     * Allows firms and all authenticated students.
     */
    private function forbidNonMarketplaceUser(Request $request): ?JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }
        if ($user->role === 'firm' || $user->role === 'student') {
            return null;
        }
        return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
    }

    private function notify(int $userId, string $type, string $title, string $body, array $data = []): void
    {
        DB::table('creator_marketplace_notifications')->insert([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data'       => json_encode($data),
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addTimeline(int $engagementId, ?int $userId, string $role, string $event, ?string $note = null, ?array $meta = null): void
    {
        DB::table('engagement_timeline')->insert([
            'engagement_id' => $engagementId,
            'user_id'       => $userId,
            'role'          => $role,
            'event'         => $event,
            'note'          => $note,
            'meta'          => $meta ? json_encode($meta) : null,
            'created_at'    => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    private function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (DB::table('creator_projects')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
