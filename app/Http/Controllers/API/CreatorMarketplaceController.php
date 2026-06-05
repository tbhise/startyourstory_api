<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreatorMarketplaceController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Dashboard stats
    // ─────────────────────────────────────────────────────────────────────────

    public function getDashboardStats(Request $request): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Create project
    // ─────────────────────────────────────────────────────────────────────────

    public function createProject(Request $request): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Update project
    // ─────────────────────────────────────────────────────────────────────────

    public function updateProject(Request $request, $id): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — List my projects
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyProjects(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

        if (! $firmProfile) {
            return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
        }

        $query = DB::table('creator_projects')
            ->where('firm_id', $firmProfile->id);

        if ($request->filled('status')) {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Project detail (with bid summary)
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyProjectDetails(Request $request, $id): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Close project
    // ─────────────────────────────────────────────────────────────────────────

    public function closeProject(Request $request, $id): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Get bids on a project
    // ─────────────────────────────────────────────────────────────────────────

    public function getProjectBids(Request $request, $projectId): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Update bid status (shortlist / select / reject)
    // ─────────────────────────────────────────────────────────────────────────

    public function updateBidStatus(Request $request, $bidId): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Browse published projects
    // ─────────────────────────────────────────────────────────────────────────

    public function browseProjects(Request $request): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Public project details + user's own bid
    // ─────────────────────────────────────────────────────────────────────────

    public function publicProjectDetails(Request $request, $id): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Submit bid
    // ─────────────────────────────────────────────────────────────────────────

    public function submitBid(Request $request, $projectId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user->role === 'firm') {
            return response()->json(['status' => false, 'message' => 'Firms cannot submit bids'], 403);
        }

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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Withdraw bid
    // ─────────────────────────────────────────────────────────────────────────

    public function withdrawBid(Request $request, $bidId): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — My bids
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyBids(Request $request): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Accept creator (notifies creator to respond)
    // ─────────────────────────────────────────────────────────────────────────

    public function acceptCreator(Request $request, $bidId): JsonResponse
    {
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

        return response()->json([
            'status'  => true,
            'message' => $alreadySelected ? 'Creator already accepted' : 'Creator accepted. Notification sent.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Selected bid details (for respond/contract preview page)
    // ─────────────────────────────────────────────────────────────────────────

    public function getSelectedBidDetails(Request $request, $bidId): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — Respond to firm's acceptance (accept or decline)
    // ─────────────────────────────────────────────────────────────────────────

    public function creatorRespondToBid(Request $request, $bidId): JsonResponse
    {
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

        return response()->json(['status' => true, 'message' => 'Project declined.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED — Engagement contract (both firm and creator)
    // ─────────────────────────────────────────────────────────────────────────

    public function getEngagement(Request $request, $id): JsonResponse
    {
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

        return response()->json([
            'status' => true,
            'data'   => [
                'engagement'   => $engagement,
                'viewer_role'  => $isFirm ? 'firm' : 'creator',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATOR — My engagements
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyEngagements(Request $request): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — My engagements
    // ─────────────────────────────────────────────────────────────────────────

    public function getFirmEngagements(Request $request): JsonResponse
    {
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED — Notifications
    // ─────────────────────────────────────────────────────────────────────────

    public function getMarketplaceNotifications(Request $request): JsonResponse
    {
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
    }

    public function markNotificationRead(Request $request, $id): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        DB::table('creator_marketplace_notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        DB::table('creator_marketplace_notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'All notifications marked as read']);
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
