@extends('emails.layouts.premium')

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
    <p>Hello,</p>

    <p>
        You requested to download <strong>{{ $materialTitle }}</strong> from the CA Library.
        Verify your email address to access your download in My Library.
    </p>

    <p style="text-align:center; margin:32px 0;">
        <a href="{{ $verificationUrl }}" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
            Verify Email &amp; Open My Library
        </a>
    </p>

    <div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
        <p>
            This verification link will expire in 60 minutes.
        </p>
    </div>

    <p style="margin-top:24px;">
        If you did not request this download, you can safely ignore this email.
    </p>
</div>
@endsection
