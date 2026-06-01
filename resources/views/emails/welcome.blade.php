@php

    $userType = $userType ?? 'student';

    switch ($userType) {
        case 'firm':
            $headline = 'Welcome to Start Your Story 🎉';

            $intro =
                'Thank you for joining Start Your Story. Your firm can now connect with talented CA students, semi-qualified professionals and Chartered Accountants from across India.';

            $description =
                'Your account has been successfully verified and activated. Complete your firm profile, publish opportunities and start connecting with suitable candidates.';

            $ctaText = 'Start Hiring';

            $nextSteps = [
                'Complete your firm profile',
                'Post job opportunities',
                'Review candidate applications',
                'Schedule interviews with applicants',
            ];

            break;

        case 'creator':
            $headline = 'Welcome to Start Your Story 🎉';

            $intro =
                'Thank you for joining Start Your Story. Build your professional brand, share your expertise and engage with aspiring finance professionals.';

            $description =
                'Your account has been successfully verified and activated. Complete your creator profile and begin building your presence within the CA community.';

            $ctaText = 'Complete Creator Profile';

            $nextSteps = [
                'Complete your creator profile',
                'Share valuable content',
                'Grow your professional audience',
                'Connect with students and professionals',
            ];

            break;

        default:
            $headline = 'Welcome to Start Your Story 🎉';

            $intro =
                'Thank you for joining Start Your Story, India\'s dedicated platform connecting CA students, semi-qualified professionals, qualified Chartered Accountants and firms.';

            $description =
                'Your account has been successfully verified and activated. Complete your profile, explore opportunities and start building meaningful professional connections.';

            $ctaText = 'Explore Opportunities';

            $nextSteps = [
                'Complete your profile',
                'Explore available opportunities',
                'Apply to relevant positions',
                'Connect with firms and professionals',
            ];

            break;
    }

@endphp

@extends('emails.layouts.app')

@section('content')
    <h1 style="margin:0 0 20px;color:#111827;font-size:30px;line-height:38px;font-weight:700;">
        {{ $headline }}
    </h1>

    <p>
        Hello <strong>{{ $name }}</strong>,
    </p>

    <p>
        {{ $intro }}
    </p>

    <p>
        {{ $description }}
    </p>

    @if (!empty($couponCode))
        <div class="info-box">


            <p style="margin-bottom:10px;font-weight:600;">
                Your Welcome Coupon
            </p>

            <p
                style="
        margin:0;
        font-size:28px;
        font-weight:800;
        letter-spacing:3px;
        color:#1d4ed8;
    ">
                {{ $couponCode }}
            </p>


        </div>
    @endif

    <p style="text-align:center;margin:35px 0;">


        <a href="https://startyourstory.in/login" class="button">
            {{ $ctaText }}
        </a>


    </p>

    <div style="
        margin-top:30px;
        padding-top:25px;
        border-top:1px solid #e5e7eb;
    ">


        <h3 style="
        margin:0 0 16px;
        color:#111827;
        font-size:18px;
    ">
            What's Next?
        </h3>

        <p style="margin:0;line-height:30px;">

            @foreach ($nextSteps as $step)
                ✓ {{ $step }}<br>
            @endforeach

        </p>

    </div>
@endsection
