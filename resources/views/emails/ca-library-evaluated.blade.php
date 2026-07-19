@extends('emails.layouts.premium')

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
    <p>Hello,</p>

    <p>
        Great news — your answer sheet for <strong>{{ $materialTitle }}</strong> has been evaluated.
        Your evaluated paper with suggestions and marks is ready in My Library.
    </p>

    <p style="text-align:center; margin:32px 0;">
        <a href="{{ $myLibraryUrl }}" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
            Open My Library
        </a>
    </p>

    <p style="margin-top:24px;">
        If your session has expired, log in again or request a new verification link from the CA Library.
    </p>
</div>
@endsection
