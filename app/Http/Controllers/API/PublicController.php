<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /contact-submission
    // ─────────────────────────────────────────────────────────────────────────

    public function submitContact(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:200',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:300',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::table('contact_submissions')->insert([
            'name'       => $request->name,
            'email'      => $request->email,
            'subject'    => $request->subject,
            'message'    => $request->message,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Admin notification feed (non-throwing)
        \App\Services\Notifications\AdminNotificationService::contactSubmission(
            $request->name,
            $request->email,
            $request->subject
        );

        // Notify admin
        try {
            $adminEmail = config('mail.admin_address', env('ADMIN_EMAIL', 'admin@startyourstory.in'));
            \Illuminate\Support\Facades\Mail::raw(
                "New contact form submission\n\nName: {$request->name}\nEmail: {$request->email}\nSubject: {$request->subject}\n\n{$request->message}",
                fn ($m) => $m->to($adminEmail)->subject("Contact Form: {$request->subject}")
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Contact form notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => true,
            'message' => "Thanks for reaching out! We'll get back to you within 24 hours.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /newsletter/subscribe
    // ─────────────────────────────────────────────────────────────────────────

    public function subscribeNewsletter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $exists = DB::table('newsletter_subscribers')
            ->where('email', $request->email)
            ->exists();

        if ($exists) {
            return response()->json(['status' => true, 'message' => "You're already subscribed — we'll keep the updates coming!"]);
        }

        DB::table('newsletter_subscribers')->insert([
            'email'         => $request->email,
            'ip_address'    => $request->ip(),
            'subscribed_at' => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Subscribed successfully! Welcome to the community.',
        ]);
    }
}
