<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FirmController;
use App\Http\Controllers\API\FirmDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Controllers\API\MasterController;
use App\Http\Controllers\API\JobsController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Middleware\FirmVerifiedMiddleware;

use App\Http\Controllers\API\TrainingPartnerController;
use App\Http\Controllers\API\CompanyEmployeeController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\AdminWalletController;
use App\Http\Controllers\API\MessagingController;
use App\Http\Controllers\API\AdminMessagingController;
use App\Http\Controllers\API\ErrorLogController;

Route::post('/registerStudent', [UserController::class, 'registerStudent']);
Route::post('/registerFirm',    [FirmController::class, 'registerFirm']);
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/logout',          [AuthController::class, 'logout']);
Route::get('/me',               [AuthController::class, 'me']);

Route::post(
    '/email/send-verification-link',
    [UserController::class, 'sendVerificationLink']
);
Route::get(
    '/email/verification-status',
    [UserController::class, 'verificationStatus']
);

Route::middleware([ApiAuthMiddleware::class])->group(function () {

    // ── Available to all authenticated users (no firm-verification gate) ──
    Route::post('/updateProfile',        [UserController::class, 'updateProfile']);
    Route::post('/getProfile',           [UserController::class, 'getProfile']);
    Route::post('/updateProfileImage',   [UserController::class, 'updateProfileImage']);
    Route::post('/students/{id}/track-recruiter-action', [UserController::class, 'trackRecruiterAction']);
    Route::post('/student/report-profile', [UserController::class, 'reportStudentProfile']);
    Route::post('/student/directory-visibility', [UserController::class, 'updateDirectoryVisibility']);

    // Company employee directory (verified members working at a firm)
    Route::post('/companies/{id}/employees/preview',         [CompanyEmployeeController::class, 'getPreview']);
    Route::post('/companies/{id}/employees',                 [CompanyEmployeeController::class, 'getDirectory']);
    Route::post('/companies/{id}/employees/category-counts', [CompanyEmployeeController::class, 'getCategoryCounts']);

    // Firm profile setup & public browsing — accessible even while pending
    Route::post('/firm_profile_update',    [FirmController::class, 'firm_profile_update']);
    Route::post('/getFirmProfileDetails',  [FirmController::class, 'getFirmProfileDetails']);
    Route::post('/getCompanies',           [FirmController::class, 'getCompanies']);
    Route::post('/getCompanyDetails/{id}', [FirmController::class, 'getCompanyDetails']);
    Route::post('/searchFirms',            [FirmController::class, 'searchFirms']);
    Route::get('/getJobs',                 [FirmController::class, 'getJobs']);

    // Student job actions
    Route::post('/jobs/{id}/apply',                          [JobsController::class, 'applyJob']);
    Route::post('/jobs/{id}/save',                           [JobsController::class, 'saveJob']);
    Route::delete('/jobs/{id}/save',                         [JobsController::class, 'saveJob']);
    Route::post('/getAppliedJobs',                           [JobsController::class, 'getAppliedJobs']);
    Route::post('/getSavedJobs',                             [JobsController::class, 'getSavedJobs']);
    Route::post('/applications/{id}/respondInterview',       [JobsController::class, 'respondInterview']);

    Route::post('/mark-read', [NotificationController::class, 'markAsRead']);

    // ── Student wallet ──
    Route::post('/wallet',                          [WalletController::class, 'getWallet']);
    Route::post('/wallet/ledger',                   [WalletController::class, 'getLedger']);
    Route::post('/wallet/recharges',                [WalletController::class, 'getRechargeHistory']);
    Route::post('/wallet/recharge/order',           [WalletController::class, 'createOrder']);
    Route::post('/wallet/recharge/verify',          [WalletController::class, 'verifyPayment']);
    Route::post('/wallet/recharge/manual',          [WalletController::class, 'submitManualRecharge']);

    // ── Firm dashboard routes — require manual verification approval ──
    Route::middleware([FirmVerifiedMiddleware::class])->group(function () {
        Route::post('/candidates',                           [FirmDashboardController::class, 'getCandidates']);
        Route::post('/candidate/{id}',                       [FirmDashboardController::class, 'candidateDetail']);
        Route::post('/downloadFile',                         [FirmDashboardController::class, 'downloadFile']);
        Route::post('/notifications',                        [FirmDashboardController::class, 'getNotifications']);

        Route::post('/createJob',                            [FirmController::class, 'createJob']);
        Route::post('/getFirmJobs',                          [FirmController::class, 'getFirmJobs']);
        Route::post('/getFirmJobDetails/{id}',               [FirmController::class, 'getFirmJobDetails']);
        Route::post('/updateJobStatus/{id}',                 [FirmController::class, 'updateJobStatus']);
        Route::post('/deleteFirmJob/{id}',                   [FirmController::class, 'deleteFirmJob']);
        Route::post('/updateJob/{id}',                       [FirmController::class, 'updateJob']);

        Route::post('/getApplications/{id}',                 [JobsController::class, 'getApplications']);
        Route::post('/applications/{id}/updateStatus',       [JobsController::class, 'updateApplicationStatus']);
        Route::post('/applications/{id}/schedule-interview', [JobsController::class, 'scheduleInterview']);
        Route::post('/getRecruiterActions',                  [JobsController::class, 'getRecruiterActions']);
    });
});



Route::post('/admin/login',   [AdminController::class, 'login']);
Route::get('/admin/me',       [AdminController::class, 'me']);
Route::post('/admin/logout',  [AdminController::class, 'logout']);

Route::post('/master/cities',              [MasterController::class, 'getCities']);
Route::post('/master/companies',           [MasterController::class, 'getCompanies']);

// Admin — firm manual verification
Route::get('/admin/firms',                 [AdminController::class, 'getPendingFirms']);
Route::post('/admin/firms/{id}/approve',   [AdminController::class, 'approveFirm']);
Route::post('/admin/firms/{id}/reject',    [AdminController::class, 'rejectFirm']);

// Admin — student wallet recharges
Route::post('/admin/wallet/recharges',                   [AdminWalletController::class, 'getRecharges']);
Route::post('/admin/wallet/recharges/{id}/approve',      [AdminWalletController::class, 'approveRecharge']);
Route::post('/admin/wallet/recharges/{id}/reject',       [AdminWalletController::class, 'rejectRecharge']);

Route::post('/admin/subscriptions',        [AdminController::class, 'getAdminSubscriptions']);
Route::post('/admin/addSubscriptions',     [AdminController::class, 'addSubscriptions']);
Route::post('/premium-requests',           [AdminController::class, 'submitPremiumRequest']);
Route::post('/admin/premium-requests',     [AdminController::class, 'getPremiumRequests']);
Route::post(
    '/admin/premium-requests/{id}/approve',
    [AdminController::class, 'approvePremiumRequest']
);
Route::post(
    '/admin/premium-requests/{id}/reject',
    [AdminController::class, 'rejectPremiumRequest']
);



Route::prefix('admin/training-partners')->group(function () {

    Route::get('/', [TrainingPartnerController::class, 'index']);

    Route::get('/{id}', [TrainingPartnerController::class, 'show']);

    Route::post('/', [TrainingPartnerController::class, 'store']);

    Route::post('/{id}', [TrainingPartnerController::class, 'update']);

    Route::delete('/{id}', [TrainingPartnerController::class, 'destroy']);

    Route::post('/{id}/toggle-active', [TrainingPartnerController::class, 'toggleActive']);
});



  Route::put(
        '/admin/training-partners/{id}',
        [TrainingPartnerController::class, 'update']
    );


    Route::get('activeTrainingPartners', [TrainingPartnerController::class, 'getActiveTrainingPartners']);


Route::post('/payments/create-order', [PaymentController::class, 'createOrder']);

Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
Route::post('/payments/failure', [PaymentController::class, 'paymentFailure']);

// ── Messaging ─────────────────────────────────────────────────────────────────
Route::middleware([ApiAuthMiddleware::class])->prefix('messaging')->group(function () {
    Route::get('/conversations',                              [MessagingController::class, 'getConversations']);
    Route::post('/conversations',                             [MessagingController::class, 'startConversation']);
    Route::get('/conversations/{id}/messages',                [MessagingController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages',               [MessagingController::class, 'sendMessage']);
    Route::post('/conversations/{id}/ignore',                 [MessagingController::class, 'ignoreRequest']);
    Route::post('/conversations/{id}/mark-read',              [MessagingController::class, 'markRead']);
    Route::get('/unread-count',                               [MessagingController::class, 'getUnreadCount']);
    Route::get('/settings',                                   [MessagingController::class, 'getSettings']);
    Route::post('/settings',                                  [MessagingController::class, 'updateSettings']);
    Route::get('/firm/{firmId}/status',                       [MessagingController::class, 'getFirmMessagingStatus']);
    Route::get('/candidate/{candidateId}/status',             [MessagingController::class, 'getCandidateMessagingStatus']);
});

// ── Admin Messaging ───────────────────────────────────────────────────────────
Route::prefix('admin/messaging')->group(function () {
    Route::get('/stats',                                      [AdminMessagingController::class, 'getStats']);
    Route::get('/conversations',                              [AdminMessagingController::class, 'getConversations']);
    Route::get('/conversations/{id}/messages',                [AdminMessagingController::class, 'getConversationMessages']);
    Route::post('/conversations/{id}/block',                  [AdminMessagingController::class, 'blockConversation']);
    Route::post('/conversations/{id}/unblock',                [AdminMessagingController::class, 'unblockConversation']);
    Route::get('/limits',                                     [AdminMessagingController::class, 'getLimits']);
    Route::post('/limits/{firmId}/reset-monthly',             [AdminMessagingController::class, 'resetMonthlyLimit']);
});

// ── Error Logging ─────────────────────────────────────────────────────────────
// Public — no auth required so errors during login/registration are captured too
Route::post('/error-logs',                [ErrorLogController::class, 'store']);

// Admin — viewing and managing logs
Route::get('/admin/error-logs',           [ErrorLogController::class, 'index']);
Route::get('/admin/error-logs/stats',     [ErrorLogController::class, 'stats']);
Route::delete('/admin/error-logs',        [ErrorLogController::class, 'destroy']);
