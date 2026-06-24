<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dynamic XML sitemaps for the public-facing frontend, structured as a
 * sitemap index so it scales as new sections (jobs, companies, resources) are
 * added later.
 *
 *   /sitemap.xml            → index, references the child sitemaps below
 *   /sitemaps/static.xml    → static marketing / legal pages
 *   /sitemaps/blogs.xml     → one <url> per published blog
 *
 * Served (via Nginx) under https://startyourstory.in/. Every <loc> points at
 * the React SPA frontend (config services.frontend_url), NOT the API domain.
 * Published blogs are pulled live from the DB, so publishing / updating /
 * unpublishing / deleting a blog is reflected automatically — no physical
 * files, no cron job, no manual editing.
 */
class SitemapController extends Controller
{
    /** Child sitemaps referenced by the index. Future: jobs.xml, companies.xml, resources.xml. */
    private const CHILD_SITEMAPS = [
        'static.xml',
        'blogs.xml',
    ];

    /** Static public pages: [path, changefreq, priority]. */
    private const STATIC_PAGES = [
        ['/',                     'weekly',  '1.0'],
        ['/blogs',                'daily',   '0.9'],
        ['/resources',            'weekly',  '0.8'],
        ['/resume-builder',       'monthly', '0.7'],
        ['/about-us',             'monthly', '0.6'],
        ['/contact',              'monthly', '0.5'],
        ['/privacy-policy',       'yearly',  '0.3'],
        ['/cookie-policy',        'yearly',  '0.3'],
        ['/terms-and-conditions', 'yearly',  '0.3'],
    ];

    /** Number of static (non-blog) URLs in the sitemap — used by the admin health check. */
    public static function staticUrlCount(): int
    {
        return count(self::STATIC_PAGES);
    }

    // ── /sitemap.xml — sitemap index ─────────────────────────────────────────

    public function index()
    {
        $base = $this->frontendBase();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (self::CHILD_SITEMAPS as $child) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . $this->esc($base . '/sitemaps/' . $child) . "</loc>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>' . "\n";

        return $this->xml($xml);
    }

    // ── /sitemaps/static.xml ─────────────────────────────────────────────────

    public function static()
    {
        $base = $this->frontendBase();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (self::STATIC_PAGES as [$path, $changefreq, $priority]) {
            $xml .= $this->urlNode($base . $path, null, $changefreq, $priority);
        }

        $xml .= '</urlset>' . "\n";

        return $this->xml($xml);
    }

    // ── /sitemaps/blogs.xml ──────────────────────────────────────────────────

    public function blogs()
    {
        $base = $this->frontendBase();

        // Published blogs only — drafts are status='draft', deleted rows are
        // hard-deleted (no soft deletes on this table), so both are excluded.
        $blogs = DB::table('blogs')
            ->where('status', 'published')
            ->select('slug', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($blogs as $blog) {
            $lastmod = $this->formatDate($blog->updated_at);
            $xml .= $this->urlNode($base . '/blogs/' . $blog->slug, $lastmod, 'monthly', '0.8');
        }

        $xml .= '</urlset>' . "\n";

        return $this->xml($xml);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function frontendBase(): string
    {
        return rtrim(config('services.frontend_url') ?: 'https://startyourstory.in', '/');
    }

    private function xml(string $body)
    {
        return response($body, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function urlNode(string $loc, ?string $lastmod, string $changefreq, string $priority): string
    {
        $node  = "  <url>\n";
        $node .= '    <loc>' . $this->esc($loc) . "</loc>\n";
        if ($lastmod) {
            $node .= '    <lastmod>' . $lastmod . "</lastmod>\n";
        }
        $node .= '    <changefreq>' . $changefreq . "</changefreq>\n";
        $node .= '    <priority>' . $priority . "</priority>\n";
        $node .= "  </url>\n";

        return $node;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function formatDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->toAtomString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
