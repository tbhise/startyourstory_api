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
// Re-engagement email CTA click tracking.
// The single "Login to Continue" button in reengagement.blade.php points here
// (signed URL built in SendReEngagementEmails). We record the click on the
// matching email_logs row, then redirect to the frontend /login. Signed
// middleware prevents id enumeration / forged inflation of click counts.
// ============================================================================
Route::get('/e/click/{emailLog}', function (\App\Models\EmailLog $emailLog) {
    $emailLog->registerClick();

    $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');
    return redirect()->away($base . '/login');
})->middleware('signed')->name('email.click');

// ============================================================================
// TEMPORARY — DEV-ONLY EMAIL PREVIEW. Safe to delete this whole block later.
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
