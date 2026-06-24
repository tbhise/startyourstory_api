<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\FirmController;
use App\Http\Controllers\API\FirmDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ResumeController;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Controllers\API\MasterController;
use App\Http\Controllers\API\JobsController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\PhonePeFirmController;
use App\Http\Controllers\API\PhonePeEngagementController;
use App\Http\Controllers\API\FirmBillingController;
use App\Http\Middleware\FirmVerifiedMiddleware;

use App\Http\Controllers\API\TrainingPartnerController;
use App\Http\Controllers\API\CompanyEmployeeController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\AdminWalletController;
use App\Http\Controllers\API\MessagingController;
use App\Http\Controllers\API\AdminMessagingController;
use App\Http\Controllers\API\ErrorLogController;
use App\Http\Controllers\API\EmailLogController;
use App\Http\Controllers\API\AdminPayoutsController;
use App\Http\Controllers\API\CreatorMarketplaceController;
use App\Http\Controllers\API\PublicController;
use App\Http\Controllers\API\AdminSettingsController;
use App\Http\Controllers\API\PaymentSettingsController;
use App\Http\Controllers\API\AdminUserController;
use App\Http\Controllers\API\SessionController;
use App\Http\Controllers\API\FreeContentController;
use App\Http\Controllers\API\AdminBlogController;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\PhonePeWalletController;
use App\Http\Controllers\API\SysCoinController;
use App\Http\Controllers\API\AdminReferralController;
use App\Http\Controllers\API\PayoutDetailsController;

// Public (no auth)
Route::post('/contact-submission',    [PublicController::class, 'submitContact'])->middleware('throttle:contact');
Route::post('/newsletter/subscribe',  [PublicController::class, 'subscribeNewsletter'])->middleware('throttle:contact');
Route::get('/platform-settings',      [AdminSettingsController::class, 'getPublicSettings']);
// Public — admin-managed manual payment destination details (bank/UPI/QR).
Route::get('/payments/instructions',  [PaymentSettingsController::class, 'instructions']);

// Public blog listing (published blogs only)
Route::get('/blogs/public',            [BlogController::class, 'getPublishedBlogs']);
Route::get('/blogs/public/categories', [BlogController::class, 'getPublicBlogCategories']);
// NOTE: keep {slug} AFTER /categories so "categories" is not captured as a slug
Route::get('/blogs/public/{slug}',     [BlogController::class, 'getPublishedBlogBySlug']);

Route::post('/registerStudent', [UserController::class, 'registerStudent'])->middleware('throttle:auth-register');
Route::post('/registerFirm',    [FirmController::class, 'registerFirm'])->middleware('throttle:auth-register');
Route::get('/firm/lookup-by-frn', [FirmController::class, 'lookupByFRN']);
Route::get('/referral/validate',  [ReferralController::class, 'validate']); // public — live form feedback
Route::post('/login',           [AuthController::class, 'login'])->middleware('throttle:auth-login');
Route::post('/logout',          [AuthController::class, 'logout']);
Route::get('/me',               [AuthController::class, 'me']);

Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:auth-forgot');
Route::post('/auth/reset-password',  [PasswordResetController::class, 'resetPassword']);

Route::post(
    '/email/send-verification-link',
    [UserController::class, 'sendVerificationLink']
)->middleware('throttle:email-verify');
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

    // ── Resume Builder drafts (one per user) ──
    Route::get('/resume',                [ResumeController::class, 'getResume']);
    Route::post('/resume',               [ResumeController::class, 'saveResume']);
    Route::post('/resume/pdf',           [ResumeController::class, 'downloadPdf'])->middleware('throttle:resume-pdf');
    // TEMPORARY — in-browser HTML preview for template development (returns text/html, not PDF).
    Route::post('/resume/preview-html',  [ResumeController::class, 'previewHtml'])->middleware('throttle:resume-pdf');
    Route::delete('/resume',             [ResumeController::class, 'deleteResume']);
    Route::post('/updateProfileImage',   [UserController::class, 'updateProfileImage']);
    Route::post('/students/{id}/track-recruiter-action', [UserController::class, 'trackRecruiterAction']);
    Route::post('/student/report-profile', [UserController::class, 'reportStudentProfile']);
    Route::post('/student/directory-visibility',        [UserController::class, 'updateDirectoryVisibility']);
    Route::post('/dismiss-apply-limit-modal',            [UserController::class, 'dismissApplyLimitModal']);

    // Student account deletion (30-day soft delete) — student-only (enforced in controller)
    Route::post('/account/request-deletion',             [UserController::class, 'requestAccountDeletion']);

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
    Route::post('/jobs/{id}/apply',                          [JobsController::class, 'applyJob'])->middleware('throttle:apply');
    Route::post('/jobs/{id}/save',                           [JobsController::class, 'saveJob']);
    Route::delete('/jobs/{id}/save',                         [JobsController::class, 'saveJob']);
    Route::post('/getAppliedJobs',                           [JobsController::class, 'getAppliedJobs']);
    Route::post('/getSavedJobs',                             [JobsController::class, 'getSavedJobs']);
    Route::post('/applications/{id}/respondInterview',       [JobsController::class, 'respondInterview']);

    Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
    Route::get('/referrals', [ReferralController::class, 'index']);

    // ── Centralized payout details (referral earners + creators) ──
    Route::get('/payout-details',  [PayoutDetailsController::class, 'show']);
    Route::post('/payout-details', [PayoutDetailsController::class, 'save']);

    // ── SYS Coins (points currency, separate from wallet money) ──
    Route::get('/sys-coins',         [SysCoinController::class, 'getAccount']);
    Route::post('/sys-coins/ledger', [SysCoinController::class, 'getLedger']);

    // ── Student wallet ──
    Route::post('/wallet',                          [WalletController::class, 'getWallet']);
    Route::get('/student/apply-status',             [WalletController::class, 'getApplyStatus']);
    Route::post('/wallet/ledger',                   [WalletController::class, 'getLedger']);
    Route::post('/wallet/recharges',                [WalletController::class, 'getRechargeHistory']);
    Route::post('/wallet/recharge/manual',          [WalletController::class, 'submitManualRecharge'])->middleware('throttle:payment-proof');
    Route::post('/student/premium-request',         [WalletController::class, 'submitPremiumRequest'])->middleware('throttle:payment-proof');

    // ── PhonePe wallet recharge (TEST MODE) ──
    Route::post('/wallet/recharge/phonepe/initiate', [PhonePeWalletController::class, 'initiate'])->middleware('throttle:payment-initiate');
    Route::post('/wallet/recharge/phonepe/verify',   [PhonePeWalletController::class, 'verify']);

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

// Admin — "Login as User" / impersonation (super_admin only; enforced in controller).
// AdminAuthMiddleware already requires a valid admin_token on these /admin/* paths.
Route::post('/admin/impersonate/stop',     [\App\Http\Controllers\API\AdminImpersonationController::class, 'stop']);
Route::post('/admin/impersonate/{userId}', [\App\Http\Controllers\API\AdminImpersonationController::class, 'start']);

// Admin — application-level system health widget
Route::get('/admin/system-health', [\App\Http\Controllers\API\AdminSystemHealthController::class, 'health']);

// Admin — notifications (Phase 1: storage + read state; auth via admin_token in controller)
Route::get('/admin/notifications',               [\App\Http\Controllers\API\AdminNotificationController::class, 'index']);
Route::get('/admin/notifications/unread-count',  [\App\Http\Controllers\API\AdminNotificationController::class, 'unreadCount']);
Route::post('/admin/notifications/{id}/read',    [\App\Http\Controllers\API\AdminNotificationController::class, 'markRead']);
Route::post('/admin/notifications/read-all',     [\App\Http\Controllers\API\AdminNotificationController::class, 'markAllRead']);
// Admin — FCM device token registration (admin-only push)
Route::post('/admin/fcm/token',   [\App\Http\Controllers\API\AdminNotificationController::class, 'registerFcmToken']);
Route::delete('/admin/fcm/token', [\App\Http\Controllers\API\AdminNotificationController::class, 'deleteFcmToken']);

// Admin — dynamic Platform Settings (system_settings; separate from key/value platform_settings)
Route::get('/admin/system-settings',         [\App\Http\Controllers\API\AdminSystemSettingController::class, 'index']);
Route::get('/admin/system-settings/audit',   [\App\Http\Controllers\API\AdminSystemSettingController::class, 'audit']);
Route::post('/admin/system-settings/{key}',  [\App\Http\Controllers\API\AdminSystemSettingController::class, 'update']);

// Admin — Payment Settings QR image (text fields use the system-settings update route above)
Route::post('/admin/payment-settings/qr',    [PaymentSettingsController::class, 'uploadQr']);
Route::delete('/admin/payment-settings/qr',  [PaymentSettingsController::class, 'deleteQr']);

// Admin — Activity Logs (audit trail). READ-ONLY: no store/update/delete by design.
Route::get('/admin/activity-logs',          [\App\Http\Controllers\API\AdminActivityLogController::class, 'index']);
Route::get('/admin/activity-logs/filters',  [\App\Http\Controllers\API\AdminActivityLogController::class, 'filters']);

// Admin — resume template management (CRUD; drives the Resume PDF rendering)
Route::get('/admin/resume-templates',                    [\App\Http\Controllers\API\ResumeTemplateController::class, 'index']);
Route::post('/admin/resume-templates',                   [\App\Http\Controllers\API\ResumeTemplateController::class, 'store']);
Route::post('/admin/resume-templates/{id}',              [\App\Http\Controllers\API\ResumeTemplateController::class, 'update']);
Route::post('/admin/resume-templates/{id}/toggle-active',[\App\Http\Controllers\API\ResumeTemplateController::class, 'toggleActive']);
Route::post('/admin/resume-templates/{id}/preview',      [\App\Http\Controllers\API\ResumeTemplateController::class, 'uploadPreview']);
Route::delete('/admin/resume-templates/{id}',            [\App\Http\Controllers\API\ResumeTemplateController::class, 'destroy']);

// Admin — admin user management (CRUD)
Route::get('/admin/users',                        [AdminUserController::class, 'index']);
Route::post('/admin/users',                       [AdminUserController::class, 'store']);
Route::post('/admin/users/{id}',                  [AdminUserController::class, 'update']);
Route::delete('/admin/users/{id}',                [AdminUserController::class, 'destroy']);
Route::post('/admin/users/{id}/toggle-active',    [AdminUserController::class, 'toggleActive']);

Route::post('/master/cities',              [MasterController::class, 'getCities']);
Route::post('/master/companies',           [MasterController::class, 'getCompanies']);

// Admin — directory listings (POST-for-list, matching other admin list endpoints)
Route::post('/admin/firms',                [AdminController::class, 'getFirms']);
Route::get('/admin/firms-stats',           [AdminController::class, 'getFirmStats']);
Route::post('/admin/students',             [AdminController::class, 'getStudents']);
Route::get('/admin/students-stats',        [AdminController::class, 'getStudentStats']);
Route::get('/admin/students/{id}',         [AdminController::class, 'getStudent']);
Route::get('/admin/students/{id}/file',    [AdminController::class, 'downloadStudentFile']);
Route::delete('/admin/students/{id}',      [AdminController::class, 'deleteStudent']);

// Admin — moderation: reported student profiles
Route::post('/admin/reported-profiles',            [AdminController::class, 'getReportedProfiles']);
Route::post('/admin/reported-profiles/{id}/status', [AdminController::class, 'updateReportStatus']);

// Admin — contact form submissions (Feedback screen)
Route::get('/admin/contact-submissions',           [AdminController::class, 'getContactSubmissions']);

// Admin — analytics (revenue reporting + dashboard stats)
Route::get('/admin/revenue-analytics', [\App\Http\Controllers\API\AdminAnalyticsController::class, 'revenue']);
Route::get('/admin/dashboard-stats',   [\App\Http\Controllers\API\AdminAnalyticsController::class, 'dashboard']);

// Admin — firm manual verification
Route::get('/admin/firms',                 [AdminController::class, 'getPendingFirms']);
Route::get('/admin/firms/{id}',            [AdminController::class, 'getFirm']);
Route::delete('/admin/firms/{id}',         [AdminController::class, 'deleteFirm']);
Route::post('/admin/firms/{id}/approve',   [AdminController::class, 'approveFirm']);
Route::post('/admin/firms/{id}/reject',    [AdminController::class, 'rejectFirm']);

// Admin — student wallet recharges
Route::post('/admin/wallet/recharges',                   [AdminWalletController::class, 'getRecharges']);
Route::post('/admin/wallet/recharges/{id}/approve',      [AdminWalletController::class, 'approveRecharge']);
Route::post('/admin/wallet/recharges/{id}/reject',       [AdminWalletController::class, 'rejectRecharge']);

// Admin — referral payouts (firm referral, real money — mark-only) + reward ledgers
Route::post('/admin/referral-payouts',                   [AdminReferralController::class, 'listPayouts']);
Route::post('/admin/referral-payouts/{id}/approve',      [AdminReferralController::class, 'approvePayout']);
Route::post('/admin/referral-payouts/{id}/mark-paid',    [AdminReferralController::class, 'markPayoutPaid']);
Route::post('/admin/referral-payouts/{id}/send-mail',    [AdminReferralController::class, 'sendPayoutDetailsMail']);
Route::post('/admin/sys-coins/transactions',             [AdminReferralController::class, 'listCoinTransactions']);
Route::post('/admin/referral-transactions',              [AdminReferralController::class, 'listReferralTransactions']);

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

// Student premium purchase requests (admin review)
Route::post('/admin/student-premium-requests',              [AdminController::class, 'getStudentPremiumRequests']);
Route::post('/admin/student-premium-requests/{id}/approve', [AdminController::class, 'approveStudentPremiumRequest']);
Route::post('/admin/student-premium-requests/{id}/reject',  [AdminController::class, 'rejectStudentPremiumRequest']);



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


Route::post('/payments/phonepe/initiate', [PhonePeFirmController::class, 'initiate'])->middleware('throttle:payment-initiate');
Route::post('/payments/phonepe/verify',   [PhonePeFirmController::class, 'verify']);
Route::post('/payments/phonepe/webhook',  [PhonePeFirmController::class, 'webhook']);
Route::get('/payments/phonepe/webhook',   fn() => response()->json(['status' => 'ok']));

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
    // Payment — firm-only actions (PhonePe)
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/phonepe/initiate', [PhonePeEngagementController::class, 'initiate'])->middleware('throttle:payment-initiate');
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/phonepe/verify',   [PhonePeEngagementController::class, 'verify']);
    Route::post('/creator-marketplace/engagements/{engagementId}/payment/manual',           [CreatorMarketplaceController::class, 'submitManualPayment'])->middleware('throttle:payment-proof');

    // Billing & Payments — read-only reporting of the firm's own subscription &
    // creator payments (no wallet, no new storage, no settlement/commission data).
    Route::get('/firm/billing-payments', [FirmBillingController::class, 'index']);
});

// ── Free Content Credits (Firm) ───────────────────────────────────────────────
Route::middleware([ApiAuthMiddleware::class, FirmVerifiedMiddleware::class])->group(function () {
    Route::get('/free-content/credits',                [FreeContentController::class, 'getCredits']);
    Route::post('/free-content/requests',              [FreeContentController::class, 'submitRequest']);
    Route::get('/free-content/requests',               [FreeContentController::class, 'getMyRequests']);
});

// ── Free Content Credits (Admin) ──────────────────────────────────────────────
Route::get('/admin/free-content-requests',                             [FreeContentController::class, 'getAdminRequests']);
Route::post('/admin/free-content-requests/{id}/confirm',               [FreeContentController::class, 'confirmRequest']);
Route::post('/admin/free-content-requests/{id}/status',                [FreeContentController::class, 'updateStatus']);
Route::post('/admin/free-content-requests/{id}/deliver',               [FreeContentController::class, 'uploadDeliverable']);
Route::post('/admin/free-content-requests/{id}/reject',                [FreeContentController::class, 'rejectRequest']);

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

// ── PhonePe webhook (no auth — PhonePe S2S; signature verified inside controller) ──
Route::post('/wallet/recharge/phonepe/webhook', [PhonePeWalletController::class, 'webhook']);
Route::get('/wallet/recharge/phonepe/webhook', fn() => response()->json(['status' => 'ok'], 200));
Route::post('/creator-marketplace/payments/phonepe/webhook', [PhonePeEngagementController::class, 'webhook']);
Route::get('/creator-marketplace/payments/phonepe/webhook',  fn() => response()->json(['status' => 'ok'], 200));

// ── Error Logging ─────────────────────────────────────────────────────────────
// Public — no auth required so errors during login/registration are captured too
Route::post('/error-logs',                [ErrorLogController::class, 'store']);

// Admin — viewing and managing logs
Route::get('/admin/error-logs',           [ErrorLogController::class, 'index']);
Route::get('/admin/error-logs/stats',     [ErrorLogController::class, 'stats']);
Route::delete('/admin/error-logs',        [ErrorLogController::class, 'destroy']);

// Admin — Email Logs (read-only analytics + click tracking)
Route::get('/admin/email-logs',           [EmailLogController::class, 'index']);
Route::get('/admin/email-logs/stats',     [EmailLogController::class, 'stats']);
Route::delete('/admin/email-logs',        [EmailLogController::class, 'destroy']);

// ── Admin — Blog Module (Phase 1) ─────────────────────────────────────────────
Route::prefix('admin/blog')->group(function () {
    // Categories
    Route::get('/categories',              [AdminBlogController::class, 'getCategories']);
    Route::post('/categories',             [AdminBlogController::class, 'createCategory']);
    Route::post('/categories/{id}',        [AdminBlogController::class, 'updateCategory']);
    Route::delete('/categories/{id}',      [AdminBlogController::class, 'deleteCategory']);

    // Tags
    Route::get('/tags',                    [AdminBlogController::class, 'getTags']);
    Route::post('/tags',                   [AdminBlogController::class, 'createTag']);
    Route::post('/tags/{id}',              [AdminBlogController::class, 'updateTag']);
    Route::delete('/tags/{id}',            [AdminBlogController::class, 'deleteTag']);

    // Blogs
    Route::get('/blogs',                   [AdminBlogController::class, 'getBlogs']);
    Route::post('/blogs',                  [AdminBlogController::class, 'createBlog']);
    Route::get('/blogs/{id}',              [AdminBlogController::class, 'getBlog']);
    Route::post('/blogs/{id}',             [AdminBlogController::class, 'updateBlog']);
    Route::delete('/blogs/{id}',           [AdminBlogController::class, 'deleteBlog']);
    Route::post('/blogs/{id}/publish',     [AdminBlogController::class, 'publishBlog']);
    Route::post('/blogs/{id}/unpublish',   [AdminBlogController::class, 'unpublishBlog']);

    // Topics (content-planning pipeline)
    Route::get('/topics',                  [AdminBlogController::class, 'getTopics']);
    Route::post('/topics',                 [AdminBlogController::class, 'createTopic']);
    Route::get('/topics/{id}',             [AdminBlogController::class, 'getTopic']);
    Route::post('/topics/{id}',            [AdminBlogController::class, 'updateTopic']);
    Route::delete('/topics/{id}',          [AdminBlogController::class, 'deleteTopic']);
});
