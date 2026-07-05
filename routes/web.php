<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\jobs\SendVerificationEmailJob;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SitemapController;
use  Illuminate\Support\Facades\Artisan;

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
// Email CTA click tracking.
// Campaign CTAs (and the re-engagement "Login to Continue" button) point here via
// a signed URL. We record the click on the matching email_logs row; for a
// campaign-linked log, the FIRST click also bumps campaigns.clicked_count. Signed
// middleware prevents id enumeration / forged inflation of click counts.
//
// NOTE: campaigns are now triggered ONLY via the admin API (/api/admin/campaigns/*)
// or the `mail:reengagement` CLI command. The old public ?key= GET trigger is gone.
// ============================================================================
Route::get('/e/click/{emailLog}', function (\App\Models\EmailLog $emailLog) {
    // Detect the first click BEFORE registering (registerClick stamps clicked_at).
    $firstClick = is_null($emailLog->clicked_at);
    $emailLog->registerClick();

    if ($firstClick && $emailLog->campaign_id) {
        \App\Models\Campaign::where('id', $emailLog->campaign_id)->increment('clicked_count');
    }

    $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');
    return redirect()->away($base . '/login');
})->middleware('signed')->name('email.click');

// ============================================================================
// DEV-ONLY EMAIL TEMPLATE GALLERY — browser previews of EVERY mailable with
// realistic sample data, for designing the blades without sending mail.
//   /dev/emails        → index of all templates
//   /dev/emails/{key}  → rendered HTML (edit blade → refresh)
// Never registered on production; the controller re-checks the environment as
// a second gate. See app/Http/Controllers/Dev/MailPreviewController.php.
// ============================================================================
if (app()->environment(['local', 'development'])) {
    Route::get('/dev/emails',            [\App\Http\Controllers\Dev\MailPreviewController::class, 'index']);
    Route::get('/dev/emails/{key}',      [\App\Http\Controllers\Dev\MailPreviewController::class, 'show']);
    Route::get('/dev/emails/{key}/send', [\App\Http\Controllers\Dev\MailPreviewController::class, 'send']);
}

// ============================================================================
// TEMPORARY — DEV-ONLY EMAIL PREVIEW. Safe to delete this whole block later.
// (Superseded by /dev/emails above, but kept: its query params preview the
// re-engagement variants — ?type=student|firm|creator&verified=0|1&profile=0|1.)
// Renders the re-engagement email in the browser using the SAME mailable +
// Blade template that real sends use (no template logic duplicated).
//   /mail-preview/reengagement?type=student|firm|creator&verified=0|1&profile=0|1
// Disabled outside local/dev so it can never be hit in production.
// ============================================================================
Route::get('/mail-preview/reengagement', function (\Illuminate\Http\Request $request) {
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
