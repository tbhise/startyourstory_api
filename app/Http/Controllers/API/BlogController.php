<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public (no-auth) blog endpoints. Only published blogs are ever exposed.
 */
class BlogController extends Controller
{
    public function getPublishedBlogs(Request $request)
    {
        $query = DB::table('blogs')
            ->leftJoin('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
            ->select(
                'blogs.id',
                'blogs.title',
                'blogs.slug',
                'blogs.excerpt',
                'blog_categories.name as category_name',
                'blog_categories.slug as category_slug',
                'blogs.published_at',
                DB::raw("CASE WHEN blogs.featured_image IS NOT NULL THEN CONCAT('" . url('/') . "/storage/', blogs.featured_image) ELSE NULL END as featured_image_url")
            )
            ->where('blogs.status', 'published');

        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($q2) use ($q) {
                $q2->where('blogs.title', 'like', $q)
                   ->orWhere('blogs.excerpt', 'like', $q);
            });
        }

        if ($request->filled('category')) {
            $query->where('blog_categories.slug', $request->category);
        }

        // $perPage = min((int) ($request->per_page ?? 10), 30);
        $perPage = 7;
        $blogs = $query->orderByDesc('blogs.published_at')->paginate($perPage);

        return response()->json(['status' => true, 'data' => $blogs]);
    }

    public function getPublishedBlogBySlug(Request $request, $slug)
    {
        $blog = DB::table('blogs')
            ->leftJoin('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
            ->select(
                'blogs.id',
                'blogs.title',
                'blogs.slug',
                'blogs.excerpt',
                'blogs.content',
                'blogs.meta_title',
                'blogs.meta_description',
                'blog_categories.name as category_name',
                'blog_categories.slug as category_slug',
                'blogs.published_at',
                'blogs.updated_at',
                DB::raw("CASE WHEN blogs.featured_image IS NOT NULL THEN CONCAT('" . url('/') . "/storage/', blogs.featured_image) ELSE NULL END as featured_image_url")
            )
            ->where('blogs.slug', $slug)
            ->where('blogs.status', 'published')
            ->first();

        if (!$blog) {
            return response()->json(['status' => false, 'message' => 'Blog not found.'], 404);
        }

        $blog->tags = DB::table('blog_tag_map')
            ->join('blog_tags', 'blog_tags.id', '=', 'blog_tag_map.tag_id')
            ->where('blog_tag_map.blog_id', $blog->id)
            ->select('blog_tags.id', 'blog_tags.name', 'blog_tags.slug')
            ->orderBy('blog_tags.name')
            ->get();

        // Prev = next-older published blog, Next = next-newer (by published_at)
        $blog->prev = DB::table('blogs')
            ->where('status', 'published')
            ->where('published_at', '<', $blog->published_at)
            ->orderByDesc('published_at')
            ->select('title', 'slug')
            ->first();

        $blog->next = DB::table('blogs')
            ->where('status', 'published')
            ->where('published_at', '>', $blog->published_at)
            ->orderBy('published_at')
            ->select('title', 'slug')
            ->first();

        return response()->json(['status' => true, 'data' => $blog]);
    }

    public function getPublicBlogCategories()
    {
        $categories = DB::table('blog_categories')
            ->join('blogs', function ($join) {
                $join->on('blogs.category_id', '=', 'blog_categories.id')
                     ->where('blogs.status', '=', 'published');
            })
            ->select(
                'blog_categories.id',
                'blog_categories.name',
                'blog_categories.slug',
                DB::raw('COUNT(blogs.id) as published_count')
            )
            ->groupBy('blog_categories.id', 'blog_categories.name', 'blog_categories.slug')
            ->orderBy('blog_categories.name')
            ->get();

        return response()->json(['status' => true, 'data' => $categories]);
    }
}
