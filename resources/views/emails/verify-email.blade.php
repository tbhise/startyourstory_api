@extends('emails.layouts.app')

@section('content')
    <p>Hello <strong>{{ $name }}</strong>,</p>

    <p>
        Verify your email address so you never miss application updates,
        interview invitations, or new opportunities from firms.
    </p>

    <p style="text-align:center; margin:32px 0;">
        <a href="{{ $verificationUrl }}" class="button">
            Verify Email Address
        </a>
    </p>

    <div class="info-box">
        <p>
            This verification link will expire in 60 minutes.
        </p>
    </div>

    <p style="margin-top:24px;">
        If you did not create an account, you can safely ignore this email.
    </p>
@endsection
