<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\jobs\SendVerificationEmailJob;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SitemapController;
use  Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Dev\MailPreviewController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Dynamic XML sitemaps — exposed via Nginx under https://startyourstory.in/
// /sitemap.xml is a sitemap index referencing the child sitemaps below.
Route::get('/sitemap.xml',          [SitemapController::class, 'index']);
Route::get('/sitemaps/static.xml',  [SitemapController::class, 'static']);
Route::get('/sitemaps/blogs.xml',   [SitemapController::class, 'blogs']);




Route::get(
    '/email/verify/{id}/{hash}',
    [UserController::class, 'verify']
)
    ->middleware('signed')
    ->name('verification.verify');


Route::get('/admin/cls', function () {
    Artisan::call('optimize:clear');
    return response()->json(['success' => true]);
});


// ============================================================================
Route::get('/e/click/{emailLog}', function (\App\Models\EmailLog $emailLog) {
    // Detect the first click BEFORE registering (registerClick stamps clicked_at).
    $firstClick = is_null($emailLog->clicked_at);
    $emailLog->registerClick();

    $campaign = $emailLog->campaign_id
        ? \App\Models\Campaign::find($emailLog->campaign_id)
        : null;

    if ($firstClick && $campaign) {
        \App\Models\Campaign::where('id', $campaign->id)->increment('clicked_count');
    }

    // Each campaign template declares where its CTA lands; anything else (and any
    // unknown/legacy template key) falls back to the original /login destination.
    $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');
    try {
        $target = $campaign
            ? \App\Services\Campaign\CampaignTemplateRegistry::ctaUrlFor($campaign->campaign_type)
            : $base . '/login';
    } catch (\InvalidArgumentException) {
        $target = $base . '/login';
    }

    return redirect()->away($target);
})->middleware('signed')->name('email.click');

// Open-tracking pixel. Returns a 1×1 transparent GIF regardless of outcome so a
// mail client never renders a broken image.
Route::get('/e/open/{emailLog}', function (\App\Models\EmailLog $emailLog) {
    $firstOpen = is_null($emailLog->opened_at);
    $emailLog->registerOpen();

    if ($firstOpen && $emailLog->campaign_id) {
        \App\Models\Campaign::where('id', $emailLog->campaign_id)->increment('opened_count');
    }

    return response(
        base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
        200,
        [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]
    );
})->middleware('signed')->name('email.open');


if (app()->environment(['local', 'development'])) {
    Route::get('/dev/emails',            [MailPreviewController::class, 'index']);
    Route::get('/dev/emails/{key}',      [MailPreviewController::class, 'show']);
    Route::get('/dev/emails/{key}/send', [MailPreviewController::class, 'send']);
}


Route::get('/mail-preview/reengagement', function (Request $request) {
    abort_unless(app()->environment(['local', 'development']), 404);

    $type     = in_array($request->query('type'), ['student', 'firm', 'creator'], true)
        ? $request->query('type')
        : 'student';
    $verified  = $request->query('verified') === '1';
    $completed = $request->query('profile') === '1';

    // Reuse the exact mailable (subject is irrelevant for the HTML preview).
    return (new \App\Mail\ReEngagementMail(
        name: 'Tushar Bhise',
        userType: $type,
        verified: $verified,
        profileCompleted: $completed,
        subjectLine: 'Preview',
        trackingUrl: 'https://startyourstory.in/login'
    ))->render();
});
