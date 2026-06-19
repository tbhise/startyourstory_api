<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminBlogController extends Controller
{
    private function getAdminUser(Request $request)
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    private function requireAdmin(Request $request)
    {
        $admin = $this->getAdminUser($request);
        if (!$admin) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        return $admin;
    }

    // ── Slug helpers ────────────────────────────────────────────────────────────

    private function generateSlug(string $text, string $table, ?int $excludeId = null): string
    {
        $base = Str::slug($text);
        if (!$base) $base = 'item';
        $slug = $base;
        $i = 1;
        while (true) {
            $query = DB::table($table)->where('slug', $slug);
            if ($excludeId) $query->where('id', '!=', $excludeId);
            if (!$query->exists()) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    // ── Blog Categories ──────────────────────────────────────────────────────────

    public function getCategories()
    {
        $categories = DB::table('blog_categories')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'data' => $categories]);
    }

    public function createCategory(Request $request)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $duplicate = DB::table('blog_categories')
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->exists();
        if ($duplicate) {
            return response()->json(['status' => false, 'message' => 'Category already exists.']);
        }

        $slug = $this->generateSlug($request->name, 'blog_categories');

        $id = DB::table('blog_categories')->insertGetId([
            'name'        => trim($request->name),
            'slug'        => $slug,
            'description' => $request->description,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $category = DB::table('blog_categories')->where('id', $id)->first();

        return response()->json([
            'status'  => true,
            'message' => 'Category created successfully.',
            'data'    => $category,
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $category = DB::table('blog_categories')->where('id', $id)->first();
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $duplicate = DB::table('blog_categories')
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            return response()->json(['status' => false, 'message' => 'Category already exists.']);
        }

        $slug = $this->generateSlug($request->name, 'blog_categories', (int) $id);

        DB::table('blog_categories')->where('id', $id)->update([
            'name'        => trim($request->name),
            'slug'        => $slug,
            'description' => $request->description,
            'updated_at'  => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Category updated successfully.']);
    }

    public function deleteCategory(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $category = DB::table('blog_categories')->where('id', $id)->first();
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category not found.'], 404);
        }

        // Detach category from blogs (set null via FK constraint behaviour)
        DB::table('blogs')->where('category_id', $id)->update(['category_id' => null]);
        DB::table('blog_categories')->where('id', $id)->delete();

        return response()->json(['status' => true, 'message' => 'Category deleted successfully.']);
    }

    // ── Blog Tags ────────────────────────────────────────────────────────────────

    public function getTags()
    {
        $tags = DB::table('blog_tags')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'data' => $tags]);
    }

    public function createTag(Request $request)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:80',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $slug = $this->generateSlug($request->name, 'blog_tags');

        $id = DB::table('blog_tags')->insertGetId([
            'name'       => trim($request->name),
            'slug'       => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tag = DB::table('blog_tags')->where('id', $id)->first();

        return response()->json([
            'status'  => true,
            'message' => 'Tag created successfully.',
            'data'    => $tag,
        ]);
    }

    public function updateTag(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $tag = DB::table('blog_tags')->where('id', $id)->first();
        if (!$tag) {
            return response()->json(['status' => false, 'message' => 'Tag not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:80',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $slug = $this->generateSlug($request->name, 'blog_tags', (int) $id);

        DB::table('blog_tags')->where('id', $id)->update([
            'name'       => trim($request->name),
            'slug'       => $slug,
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Tag updated successfully.']);
    }

    public function deleteTag(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $tag = DB::table('blog_tags')->where('id', $id)->first();
        if (!$tag) {
            return response()->json(['status' => false, 'message' => 'Tag not found.'], 404);
        }

        DB::table('blog_tag_map')->where('tag_id', $id)->delete();
        DB::table('blog_tags')->where('id', $id)->delete();

        return response()->json(['status' => true, 'message' => 'Tag deleted successfully.']);
    }

    // ── Blogs ────────────────────────────────────────────────────────────────────

    public function getBlogs(Request $request)
    {
        $query = DB::table('blogs')
            ->leftJoin('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
            ->select(
                'blogs.id',
                'blogs.title',
                'blogs.slug',
                'blogs.excerpt',
                'blogs.social_caption',
                'blogs.status',
                'blogs.category_id',
                'blog_categories.name as category_name',
                'blogs.published_at',
                'blogs.created_at',
                'blogs.updated_at',
                DB::raw("CASE WHEN blogs.featured_image IS NOT NULL THEN CONCAT('" . url('/') . "/storage/', blogs.featured_image) ELSE NULL END as featured_image_url")
            );

        if ($request->filled('status') && in_array($request->status, ['draft', 'published'])) {
            $query->where('blogs.status', $request->status);
        }

        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($q2) use ($q) {
                $q2->where('blogs.title', 'like', $q)
                   ->orWhere('blogs.slug', 'like', $q)
                   ->orWhere('blogs.excerpt', 'like', $q);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('blogs.category_id', $request->category_id);
        }

        $perPage = min((int) ($request->per_page ?? 15), 50);
        $blogs = $query->orderByDesc('blogs.id')->paginate($perPage);

        return response()->json(['status' => true, 'data' => $blogs]);
    }

    public function getBlog(Request $request, $id)
    {
        $blog = DB::table('blogs')
            ->leftJoin('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
            ->select(
                'blogs.*',
                'blog_categories.name as category_name',
                DB::raw("CASE WHEN blogs.featured_image IS NOT NULL THEN CONCAT('" . url('/') . "/storage/', blogs.featured_image) ELSE NULL END as featured_image_url")
            )
            ->where('blogs.id', $id)
            ->first();

        if (!$blog) {
            return response()->json(['status' => false, 'message' => 'Blog not found.'], 404);
        }

        $tagIds = DB::table('blog_tag_map')
            ->where('blog_id', $id)
            ->pluck('tag_id')
            ->toArray();

        $blog->tag_ids = $tagIds;

        $blog->topic_id = DB::table('blog_topics')->where('blog_id', $id)->value('id');

        return response()->json(['status' => true, 'data' => $blog]);
    }

    public function createBlog(Request $request)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $validator = Validator::make($request->all(), [
            'title'            => 'required|string|max:300',
            'content'          => 'required|string',
            'excerpt'          => 'nullable|string|max:1000',
            'meta_title'       => 'nullable|string|max:300',
            'meta_description' => 'nullable|string|max:500',
            'social_caption'   => 'nullable|string',
            'status'           => 'nullable|in:draft,published',
            'category_id'      => 'nullable|integer|exists:blog_categories,id',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'integer|exists:blog_tags,id',
            'featured_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'slug'             => 'nullable|string|max:350',
            'topic_id'         => 'nullable|integer|exists:blog_topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $slug = $request->filled('slug')
                ? $this->generateSlug($request->slug, 'blogs')
                : $this->generateSlug($request->title, 'blogs');

            $featuredImage = null;
            if ($request->hasFile('featured_image')) {
                // Optimise + convert to WebP (falls back to original if GD unavailable).
                $featuredImage = ImageHelper::optimizeToWebp(
                    $request->file('featured_image'),
                    'blog-images/featured'
                );
            }

            $status      = $request->status ?? 'draft';
            $publishedAt = $status === 'published' ? now() : null;
            $topicId     = $request->filled('topic_id') ? (int) $request->topic_id : null;

            $result = DB::transaction(function () use ($request, $slug, $featuredImage, $status, $publishedAt, $topicId) {
                $id = DB::table('blogs')->insertGetId([
                    'title'            => trim($request->title),
                    'slug'             => $slug,
                    'excerpt'          => $request->excerpt,
                    'content'          => $request->content,
                    'featured_image'   => $featuredImage,
                    'meta_title'       => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'social_caption'   => $request->filled('social_caption') ? $request->social_caption : null,
                    'status'           => $status,
                    'category_id'      => $request->category_id ?: null,
                    'published_at'     => $publishedAt,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                if (!empty($request->tag_ids)) {
                    $rows = array_map(fn($tid) => ['blog_id' => $id, 'tag_id' => $tid], $request->tag_ids);
                    DB::table('blog_tag_map')->insert($rows);
                }

                if ($topicId) {
                    DB::table('blog_topics')
                        ->where('id', $topicId)
                        ->update([
                            'status'     => 'published',
                            'blog_id'    => $id,
                            'updated_at' => now(),
                        ]);
                }

                return ['id' => $id, 'slug' => $slug];
            });

            $blogId = $result['id'];
            $title  = trim($request->title);
            AdminActivityLogger::log($auth, AdminActivityLogger::BLOG_CREATED, 'blog', $blogId, "Created blog '{$title}'.", $request);

            return response()->json([
                'status'  => true,
                'message' => 'Blog created successfully.',
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminBlogController::createBlog', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    public function updateBlog(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $blog = DB::table('blogs')->where('id', $id)->first();
        if (!$blog) {
            return response()->json(['status' => false, 'message' => 'Blog not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'            => 'required|string|max:300',
            'content'          => 'required|string',
            'excerpt'          => 'nullable|string|max:1000',
            'meta_title'       => 'nullable|string|max:300',
            'meta_description' => 'nullable|string|max:500',
            'social_caption'   => 'nullable|string',
            'status'           => 'nullable|in:draft,published',
            'category_id'      => 'nullable|integer|exists:blog_categories,id',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'integer|exists:blog_tags,id',
            'featured_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'slug'             => 'nullable|string|max:350',
            'topic_id'         => 'nullable|integer|exists:blog_topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $slugInput = $request->filled('slug') ? $request->slug : $request->title;
            $slug = $this->generateSlug($slugInput, 'blogs', (int) $id);

            $data = [
                'title'            => trim($request->title),
                'slug'             => $slug,
                'excerpt'          => $request->excerpt,
                'content'          => $request->content,
                'meta_title'       => $request->meta_title,
                'meta_description' => $request->meta_description,
                'social_caption'   => $request->filled('social_caption') ? $request->social_caption : null,
                'status'           => $request->status ?? $blog->status,
                'category_id'      => $request->category_id ?: null,
                'updated_at'       => now(),
            ];

            // If publishing for the first time, stamp published_at
            if ($data['status'] === 'published' && !$blog->published_at) {
                $data['published_at'] = now();
            }
            // If reverting to draft, clear published_at
            if ($data['status'] === 'draft') {
                $data['published_at'] = null;
            }

            if ($request->hasFile('featured_image')) {
                if ($blog->featured_image) {
                    Storage::disk('public')->delete($blog->featured_image);
                }
                // Optimise + convert to WebP (falls back to original if GD unavailable).
                $data['featured_image'] = ImageHelper::optimizeToWebp(
                    $request->file('featured_image'),
                    'blog-images/featured'
                );
            }

            // topic_id sent as empty string means "clear"; filled means "link this topic"
            $newTopicId = $request->filled('topic_id') ? (int) $request->topic_id : null;

            DB::transaction(function () use ($id, $data, $request, $newTopicId) {
                DB::table('blogs')->where('id', $id)->update($data);

                // Sync tags
                if ($request->has('tag_ids')) {
                    DB::table('blog_tag_map')->where('blog_id', $id)->delete();
                    $tagIds = $request->tag_ids ?? [];
                    if (!empty($tagIds)) {
                        $rows = array_map(fn($tid) => ['blog_id' => $id, 'tag_id' => $tid], $tagIds);
                        DB::table('blog_tag_map')->insert($rows);
                    }
                }

                // Sync topic link
                $currentTopic = DB::table('blog_topics')->where('blog_id', $id)->first();

                if ($newTopicId === null) {
                    // Admin cleared the topic — unlink if one was set
                    if ($currentTopic) {
                        DB::table('blog_topics')->where('id', $currentTopic->id)->update([
                            'blog_id'    => null,
                            'status'     => 'generated',
                            'updated_at' => now(),
                        ]);
                    }
                } elseif (!$currentTopic || $currentTopic->id !== $newTopicId) {
                    // Different topic selected — unlink old, link new
                    if ($currentTopic) {
                        DB::table('blog_topics')->where('id', $currentTopic->id)->update([
                            'blog_id'    => null,
                            'status'     => 'generated',
                            'updated_at' => now(),
                        ]);
                    }
                    DB::table('blog_topics')->where('id', $newTopicId)->update([
                        'blog_id'    => $id,
                        'status'     => 'published',
                        'updated_at' => now(),
                    ]);
                }
                // same topic as before — no change needed
            });

            AdminActivityLogger::log($auth, AdminActivityLogger::BLOG_UPDATED, 'blog', $id, "Updated blog #{$id}.", $request);

            return response()->json(['status' => true, 'message' => 'Blog updated successfully.']);
        } catch (\Throwable $e) {
            Log::error('AdminBlogController::updateBlog', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    public function deleteBlog(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $blog = DB::table('blogs')->where('id', $id)->first();
        if (!$blog) {
            return response()->json(['status' => false, 'message' => 'Blog not found.'], 404);
        }

        if ($blog->featured_image) {
            Storage::disk('public')->delete($blog->featured_image);
        }

        DB::table('blog_tag_map')->where('blog_id', $id)->delete();
        DB::table('blogs')->where('id', $id)->delete();

        AdminActivityLogger::log($auth, AdminActivityLogger::BLOG_DELETED, 'blog', $id, "Deleted blog #{$id}.", $request);

        return response()->json(['status' => true, 'message' => 'Blog deleted successfully.']);
    }

    public function publishBlog(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $blog = DB::table('blogs')->where('id', $id)->first();
        if (!$blog) {
            return response()->json(['status' => false, 'message' => 'Blog not found.'], 404);
        }

        if ($blog->status === 'published') {
            return response()->json(['status' => false, 'message' => 'Blog is already published.'], 422);
        }

        DB::table('blogs')->where('id', $id)->update([
            'status'       => 'published',
            'published_at' => $blog->published_at ?? now(),
            'updated_at'   => now(),
        ]);

        AdminActivityLogger::log($auth, AdminActivityLogger::BLOG_PUBLISHED, 'blog', $id, "Published blog #{$id}.", $request);

        return response()->json(['status' => true, 'message' => 'Blog published successfully.']);
    }

    public function unpublishBlog(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $blog = DB::table('blogs')->where('id', $id)->first();
        if (!$blog) {
            return response()->json(['status' => false, 'message' => 'Blog not found.'], 404);
        }

        if ($blog->status === 'draft') {
            return response()->json(['status' => false, 'message' => 'Blog is already a draft.'], 422);
        }

        DB::table('blogs')->where('id', $id)->update([
            'status'     => 'draft',
            'updated_at' => now(),
        ]);

        AdminActivityLogger::log($auth, AdminActivityLogger::BLOG_UNPUBLISHED, 'blog', $id, "Unpublished blog #{$id}.", $request);

        return response()->json(['status' => true, 'message' => 'Blog unpublished (reverted to draft).']);
    }

    // ── Blog Topics (content-planning pipeline) ──────────────────────────────────

    public function getTopics(Request $request)
    {
        $query = DB::table('blog_topics')
            ->leftJoin('blog_categories', 'blog_categories.id', '=', 'blog_topics.category_id')
            ->leftJoin('blogs', 'blogs.id', '=', 'blog_topics.blog_id')
            ->select(
                'blog_topics.*',
                'blog_categories.name as category_name',
                'blogs.title as blog_title',
                'blogs.slug as blog_slug',
                'blogs.status as blog_status'
            );

        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($q2) use ($q) {
                $q2->where('blog_topics.title', 'like', $q)
                   ->orWhere('blog_topics.slug', 'like', $q)
                   ->orWhere('blog_topics.target_keywords', 'like', $q);
            });
        }

        if ($request->filled('status') && in_array($request->status, ['pending', 'generating', 'generated', 'published', 'rejected'])) {
            $query->where('blog_topics.status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('blog_topics.category_id', $request->category_id);
        }

        if ($request->filled('priority') && in_array($request->priority, ['low', 'medium', 'high'])) {
            $query->where('blog_topics.priority', $request->priority);
        }

        $perPage = min((int) ($request->per_page ?? 15), 50);
        $topics = $query->orderByDesc('blog_topics.id')->paginate($perPage);

        return response()->json(['status' => true, 'data' => $topics]);
    }

    public function getTopic(Request $request, $id)
    {
        $topic = DB::table('blog_topics')
            ->leftJoin('blog_categories', 'blog_categories.id', '=', 'blog_topics.category_id')
            ->leftJoin('blogs', 'blogs.id', '=', 'blog_topics.blog_id')
            ->select(
                'blog_topics.*',
                'blog_categories.name as category_name',
                'blogs.title as blog_title',
                'blogs.slug as blog_slug'
            )
            ->where('blog_topics.id', $id)
            ->first();

        if (!$topic) {
            return response()->json(['status' => false, 'message' => 'Topic not found.'], 404);
        }

        return response()->json(['status' => true, 'data' => $topic]);
    }

    public function createTopic(Request $request)
    {
        $admin = $this->getAdminUser($request);
        if (!$admin) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'required|string|max:300',
            'category_id'     => 'nullable|integer|exists:blog_categories,id',
            'target_keywords' => 'nullable|string|max:2000',
            'search_intent'   => 'nullable|string|max:50',
            'notes'           => 'nullable|string|max:5000',
            'priority'        => 'nullable|in:low,medium,high',
            'status'          => 'nullable|in:pending,generating,generated,published,rejected',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $slug = $this->generateSlug($request->title, 'blog_topics');

            $id = DB::table('blog_topics')->insertGetId([
                'title'             => trim($request->title),
                'slug'              => $slug,
                'category_id'       => $request->category_id ?: null,
                'target_keywords'   => $request->target_keywords,
                'search_intent'     => $request->search_intent,
                'notes'             => $request->notes,
                'priority'          => $request->priority ?? 'medium',
                'status'            => $request->status ?? 'pending',
                'generation_source' => 'manual',
                'created_by'        => $admin->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Topic created successfully.',
                'data'    => ['id' => $id, 'slug' => $slug],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminBlogController::createTopic', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    public function updateTopic(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $topic = DB::table('blog_topics')->where('id', $id)->first();
        if (!$topic) {
            return response()->json(['status' => false, 'message' => 'Topic not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'required|string|max:300',
            'category_id'     => 'nullable|integer|exists:blog_categories,id',
            'target_keywords' => 'nullable|string|max:2000',
            'search_intent'   => 'nullable|string|max:50',
            'notes'           => 'nullable|string|max:5000',
            'priority'        => 'nullable|in:low,medium,high',
            'status'          => 'nullable|in:pending,generating,generated,published,rejected',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $slug = $this->generateSlug($request->title, 'blog_topics', (int) $id);

            DB::table('blog_topics')->where('id', $id)->update([
                'title'           => trim($request->title),
                'slug'            => $slug,
                'category_id'     => $request->category_id ?: null,
                'target_keywords' => $request->target_keywords,
                'search_intent'   => $request->search_intent,
                'notes'           => $request->notes,
                'priority'        => $request->priority ?? $topic->priority,
                'status'          => $request->status ?? $topic->status,
                'updated_at'      => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Topic updated successfully.']);
        } catch (\Throwable $e) {
            Log::error('AdminBlogController::updateTopic', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    public function deleteTopic(Request $request, $id)
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) return $auth;

        $topic = DB::table('blog_topics')->where('id', $id)->first();
        if (!$topic) {
            return response()->json(['status' => false, 'message' => 'Topic not found.'], 404);
        }

        DB::table('blog_topics')->where('id', $id)->delete();

        return response()->json(['status' => true, 'message' => 'Topic deleted successfully.']);
    }
}
