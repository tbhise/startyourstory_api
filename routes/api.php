<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\ReferralController;
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
use App\Http\Controllers\API\AdminPayoutsController;
use App\Http\Controllers\API\CreatorMarketplaceController;
use App\Http\Controllers\API\PublicController;
use App\Http\Controllers\API\AdminSettingsController;
use App\Http\Controllers\API\AdminUserController;
use App\Http\Controllers\API\SessionController;

// Public (no auth)
Route::post('/contact-submission',    [PublicController::class, 'submitContact']);
Route::post('/newsletter/subscribe',  [PublicController::class, 'subscribeNewsletter']);
Route::get('/platform-settings',      [AdminSettingsController::class, 'getPublicSettings']);

Route::post('/registerStudent', [UserController::class, 'registerStudent']);
Route::post('/registerFirm',    [FirmController::class, 'registerFirm']);
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/logout',          [AuthController::class, 'logout']);
Route::get('/me',               [AuthController::class, 'me']);

Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/auth/reset-password',  [PasswordResetController::class, 'resetPassword']);

Route::post(
    '/email/send-verification-link',
    [UserController::class, 'sendVerificationLink']
);
Route::get(
    '/email/verification-status',
    [UserController::class, 'verificationStatus']
);

Route::middleware([ApiAuthMiddleware::class])->group(function () {

    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // ── Session management ──
    Route::get('/sessions',           [SessionController::class, 'index']);
    Route::delete('/sessions/all',    [SessionController::class, 'destroyAll']);   // must be before /{id}
    Route::delete('/sessions/{id}',   [SessionController::class, 'destroy']);
    Route::get('/login-history',      [SessionController::class, 'loginHistory']);

    // ── Available to all authenticated users (no firm-verification gate) ──
    Route::post('/updateProfile',        [UserController::class, 'updateProfile']);
    Route::post('/getProfile',           [UserController::class, 'getProfile']);
    Route::post('/updateProfileImage',   [UserController::class, 'updateProfileImage']);
    Route::post('/students/{id}/track-recruiter-action', [UserController::class, 'trackRecruiterAction']);
    Route::post('/student/report-profile', [UserController::class, 'reportStudentProfile']);
    Route::post('/student/directory-visibility',        [UserController::class, 'updateDirectoryVisibility']);
    Route::post('/dismiss-apply-limit-modal',            [UserController::class, 'dismissApplyLimitModal']);

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
    Route::get('/referrals', [ReferralController::class, 'index']);

    // ── Student wallet ──
    Route::post('/wallet',                          [WalletController::class, 'getWallet']);
    Route::get('/student/apply-status',             [WalletController::class, 'getApplyStatus']);
    Route::post('/wallet/ledger',                   [WalletController::class, 'getLedger']);
    Route::post('/wallet/recharges',                [WalletController::class, 'getRechargeHistory']);
    Route::post('/wallet/recharge/order',           [WalletController::class, 'createOrder']);
    Route::post('/wallet/recharge/verify',          [WalletController::class, 'verifyPayment']);
    Route::post('/wallet/recharge/manual',          [WalletController::class, 'submitManualRecharge']);
    Route::post('/student/premium-request',         [WalletController::class, 'submitPremiumRequest']);

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
        Route::post('/applications/{id}/updateStatus',         [JobsController::class, 'updateApplicationStatus']);
        Route::post('/applications/{id}/schedule-interview',  [JobsController::class, 'scheduleInterview']);
        Route::post('/applications/{id}/accept-reschedule',   [JobsController::class, 'acceptReschedule']);
        Route::post('/getRecruiterActions',                  [JobsController::class, 'getRecruiterActions']);
    });
});



Route::post('/admin/login',   [AdminController::class, 'login']);
Route::get('/admin/me',       [AdminController::class, 'me']);
Route::post('/admin/logout',  [AdminController::class, 'logout']);

// Admin — admin user management (CRUD)
Route::get('/admin/users',                        [AdminUserController::class, 'index']);
Route::post('/admin/users',                       [AdminUserController::class, 'store']);
Route::post('/admin/users/{id}',                  [AdminUserController::class, 'update']);
Route::delete('/admin/users/{id}',                [AdminUserController::class, 'destroy']);
Route::post('/admin/users/{id}/toggle-active',    [AdminUserController::class, 'toggleActive']);

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

// ── Creator Marketplace ───────────────────────────────────────────────────────
// Public — browse without login
Route::get('/creator-marketplace/projects',           [CreatorMarketplaceController::class, 'browseProjects']);
Route::get('/creator-marketplace/projects/{id}',      [CreatorMarketplaceController::class, 'publicProjectDetails']);

// Authenticated — any logged-in user
Route::middleware([ApiAuthMiddleware::class])->group(function () {
    // Creator bid actions
    Route::get('/creator-marketplace/my-bids',               [CreatorMarketplaceController::class, 'getMyBids']);
    Route::post('/creator-marketplace/bids/{projectId}',     [CreatorMarketplaceController::class, 'submitBid']);
    Route::post('/creator-marketplace/bids/{bidId}/withdraw',[CreatorMarketplaceController::class, 'withdrawBid']);
    // Creator acceptance workflow
    Route::get('/creator-marketplace/bids/{bidId}/contract', [CreatorMarketplaceController::class, 'getSelectedBidDetails']);
    Route::post('/creator-marketplace/bids/{bidId}/respond', [CreatorMarketplaceController::class, 'creatorRespondToBid']);
    // Engagements
    Route::get('/creator-marketplace/engagements/{id}',      [CreatorMarketplaceController::class, 'getEngagement']);
    Route::get('/creator-marketplace/my-engagements',        [CreatorMarketplaceController::class, 'getMyEngagements']);
    // Payment — readable by both parties
    Route::get('/creator-marketplace/engagements/{engagementId}/payment', [CreatorMarketplaceController::class, 'getEngagementPayment']);
    // Workspace — role verified in controller; creator uses submit-work, firm uses brief/approve/revision
    Route::get('/creator-marketplace/engagements/{id}/workspace',                           [CreatorMarketplaceController::class, 'getWorkspace']);
    Route::post('/creator-marketplace/engagements/{id}/brief',                              [CreatorMarketplaceController::class, 'saveBrief']);
    Route::delete('/creator-marketplace/engagements/{id}/brief/attachments/{attachmentId}', [CreatorMarketplaceController::class, 'deleteBriefAttachment']);
    Route::post('/creator-marketplace/engagements/{id}/submit-work',                        [CreatorMarketplaceController::class, 'submitDeliverable']);
    Route::post('/creator-marketplace/engagements/{id}/request-revision',                   [CreatorMarketplaceController::class, 'requestRevision']);
    Route::post('/creator-marketplace/engagements/{id}/approve',                            [CreatorMarketplaceController::class, 'approveDeliverable']);
    // Notifications
    Route::get('/creator-marketplace/notifications',                     [CreatorMarketplaceController::class, 'getMarketplaceNotifications']);
    Route::post('/creator-marketplace/notifications/read-all',           [CreatorMarketplaceController::class, 'markAllNotificationsRead']);
    Route::post('/creator-marketplace/notifications/{id}/read',          [CreatorMarketplaceController::class, 'markNotificationRead']);
    // Bank details + payout status (creator)
    Route::get('/creator-marketplace/bank-details',                       [CreatorMarketplaceController::class, 'getBankDetails']);
    Route::post('/creator-marketplace/bank-details',                      [CreatorMarketplaceController::class, 'saveBankDetails']);
    Route::get('/creator-marketplace/engagements/{engagementId}/payout',  [CreatorMarketplaceController::class, 'getPayoutStatus']);
    // Bid detail + earnings (creator)
    Route::get('/creator-marketplace/bids/{bidId}/details',               [CreatorMarketplaceController::class, 'getBidDetail']);
    Route::get('/creator-marketplace/my-earnings',                        [CreatorMarketplaceController::class, 'getMyEarnings']);
});

// Firm-verified — project management + payments
Route::middleware([ApiAuthMiddleware::class, FirmVerifiedMiddleware::class])->group(function () {
    Route::get('/creator-marketplace/dashboard',                  [CreatorMarketplaceController::class, 'getDashboardStats']);
    Route::post('/creator-marketplace/projects',                  [CreatorMarketplaceController::class, 'createProject']);
    Route::post('/creator-marketplace/my-projects',               [CreatorMarketplaceController::class, 'getMyProjects']);
    Route::get('/creator-marketplace/my-projects/{id}',           [CreatorMarketplaceController::class, 'getMyProjectDetails']);
    Route::post('/creator-marketplace/projects/{id}/update',      [CreatorMarketplaceController::class, 'updateProject']);
    Route::post('/creator-marketplace/projects/{id}/close',       [CreatorMarketplaceController::class, 'closeProject']);
    Route::get('/creator-marketplace/projects/{id}/bids',         [CreatorMarketplaceController::class, 'getProjectBids']);
    Route::post('/creator-marketplace/bids/{bidId}/status',       [CreatorMarketplaceController::class, 'updateBidStatus']);
    Route::post('/creator-marketplace/bids/{bidId}/accept-creator',[CreatorMarketplaceController::class, 'acceptCreator']);
    Route::get('/creator-marketplace/firm-engagements',           [CreatorMarketplaceController::class, 'getFirmEngagements']);
    // Payment — firm-only actions
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/initiate', [CreatorMarketplaceController::class, 'initiatePayment']);
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/verify',   [CreatorMarketplaceController::class, 'verifyEngagementPayment']);
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/failure',  [CreatorMarketplaceController::class, 'engagementPaymentFailure']);
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/manual',   [CreatorMarketplaceController::class, 'submitManualPayment']);
});

// ── Admin — Creator Marketplace Payments ─────────────────────────────────────
Route::get('/admin/creator-engagements/{id}',          [AdminController::class, 'getEngagementSummary']);
Route::get('/admin/creator-payments',                  [AdminController::class, 'getCreatorPayments']);
Route::post('/admin/creator-payments/{id}/approve',    [AdminController::class, 'approveCreatorPayment']);
Route::post('/admin/creator-payments/{id}/reject',     [AdminController::class, 'rejectCreatorPayment']);

// ── Admin — Creator Payouts ───────────────────────────────────────────────────
Route::get('/admin/creator-payouts',                       [AdminPayoutsController::class, 'getPayouts']);
Route::get('/admin/creator-payouts/stats',                 [AdminPayoutsController::class, 'getStats']);
Route::get('/admin/creator-payouts/pending-count',         [AdminPayoutsController::class, 'pendingCount']);
Route::post('/admin/creator-payouts/flush-approved',       [AdminPayoutsController::class, 'flushApproved']);
Route::post('/admin/creator-payouts/{id}/mark-paid',       [AdminPayoutsController::class, 'markPaid']);
Route::post('/admin/creator-payouts/{id}/mark-failed',     [AdminPayoutsController::class, 'markFailed']);
Route::get('/admin/commission-rate',                   [AdminPayoutsController::class, 'getCommissionRate']);
Route::post('/admin/commission-rate',                  [AdminPayoutsController::class, 'updateCommissionRate']);
Route::get('/admin/platform-settings',                 [AdminSettingsController::class, 'getSettings']);
Route::post('/admin/platform-settings/{key}',          [AdminSettingsController::class, 'updateSetting']);

// ── Error Logging ─────────────────────────────────────────────────────────────
// Public — no auth required so errors during login/registration are captured too
Route::post('/error-logs',                [ErrorLogController::class, 'store']);

// Admin — viewing and managing logs
Route::get('/admin/error-logs',           [ErrorLogController::class, 'index']);
Route::get('/admin/error-logs/stats',     [ErrorLogController::class, 'stats']);
Route::delete('/admin/error-logs',        [ErrorLogController::class, 'destroy']);
