<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
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
// Re-engagement campaign trigger (TEMPORARY, short-term internal use).
// Runs the existing `mail:reengagement` command from the browser, mirroring
// /admin/cls above. The command's handle() is UNTOUCHED — query params just
// map onto its CLI options.
//
//   - Gated by ?key=<secret> (403 without it).
//   - SAFE BY DEFAULT: without ?confirm=SEND it runs a DRY RUN (sends nothing).
//   - The command pauses ~1s per email, so the whole base (≤100) takes roughly
//     that many seconds. PHP's time limit is lifted here; if your nginx/PHP-FPM
//     request timeout still cuts it off, either raise that timeout, batch with
//     &limit=N, or use &background=1 to run the whole command on the queue
//     (returns instantly; needs a running queue worker; results in email_logs).
//
//   Preview everyone:   /admin/send-reengagement?key=KEY
//   Test to TEST_EMAIL: /admin/send-reengagement?key=KEY&confirm=SEND&test=1&limit=1
//   Send all for real:  /admin/send-reengagement?key=KEY&confirm=SEND
//   Send in background: /admin/send-reengagement?key=KEY&confirm=SEND&background=1
// ============================================================================
Route::get('/admin/send-reengagement', function (Request $request) {
    // env() returns null once `php artisan config:cache` has run, so fall back
    // to a constant. Change this secret as needed (temporary internal tool).
    $secret = 'sys-7f3a9';
    if ((string) $request->query('key') !== $secret) {
        return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
    }

    // Safe default: dry-run unless the caller explicitly confirms a real send.
    $confirmed = $request->query('confirm') === 'SEND';

    // Map query params -> artisan options. The command validates the values.
    $options = [];
    if (!$confirmed) {
        $options['--dry-run'] = true;
    }
    foreach (['type', 'verified', 'profile', 'limit'] as $opt) {
        $val = $request->query($opt);
        if ($val !== null && $val !== '') {
            $options['--' . $opt] = $val;
        }
    }
    if (filter_var($request->query('test'), FILTER_VALIDATE_BOOLEAN)) {
        $options['--test'] = true;
    }

    // Optional: queue the WHOLE command so the request returns immediately and
    // handle() runs on the worker (unchanged). Requires a running queue worker.
    if ($confirmed && filter_var($request->query('background'), FILTER_VALIDATE_BOOLEAN)) {
        Artisan::queue('mail:reengagement', $options);
        return response()->json([
            'success' => true,
            'mode'    => 'queued',
            'message' => 'Campaign dispatched to the queue. Check email_logs for results.',
        ]);
    }

    // Synchronous run: ~1s per email; lift PHP's time limit so a ≤100 run can
    // finish inline (server-level FPM/nginx timeouts may still apply).
    @set_time_limit(0);
    @ignore_user_abort(true);

    $exit = Artisan::call('mail:reengagement', $options);

    return response()->json([
        'success'   => $exit === 0,
        'mode'      => $confirmed ? 'send' : 'dry_run',
        'exit_code' => $exit,
        'output'    => Artisan::output(),
    ]);
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
