<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\jobs\SendVerificationEmailJob;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});




Route::get('/test-verification-mail', function () {

    $user = \App\Models\User::first();

    SendVerificationEmailJob::dispatch($user);

    return 'Verification mail queued';
});





Route::get('/email/verify/{id}/{hash}', function (
    Request $request,
    $id,
    $hash
) {

    if (! $request->hasValidSignature()) {

        return redirect()->away(
            env('FRONTEND_URL')
                . '/email-verification-result?status=failed'
        );
    }

    $user = User::find($id);

    if (! $user) {

        return redirect()->away(
            env('FRONTEND_URL')
                . '/email-verification-result?status=failed'
        );
    }

    if (! hash_equals(
        sha1($user->email),
        $hash
    )) {

        return redirect()->away(
            env('FRONTEND_URL')
                . '/email-verification-result?status=failed'
        );
    }

    if (is_null($user->email_verified_at)) {

        $user->email_verified_at = now();
        $user->save();
    }

    return redirect()->away(
        env('FRONTEND_URL')
            . '/email-verification-result?status=success'
    );
})->middleware('signed')
    ->name('verification.verify');
