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

    if ($firstClick && $emailLog->campaign_id) {
        \App\Models\Campaign::where('id', $emailLog->campaign_id)->increment('clicked_count');
    }

    $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');
    return redirect()->away($base . '/login');
})->middleware('signed')->name('email.click');


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
