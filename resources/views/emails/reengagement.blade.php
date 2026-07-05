@php
    // Self-contained template: derives heading/lead/benefits from userType +
    // verified + profileCompleted passed in by ReEngagementMail (or the preview
    // route). Keeping this computation inside the view means the mailable/command
    // pass only name, userType, verified, profileCompleted and trackingUrl.
    $userType         = $userType ?? 'student';
    $verified         = $verified ?? false;
    $profileCompleted = $profileCompleted ?? false;
    $trackingUrl      = $trackingUrl ?? '#';

    // Three lifecycle states: unverified → (verified) incomplete → complete.
    $state = !$verified ? 'unverified' : (!$profileCompleted ? 'incomplete' : 'complete');

    switch ($userType) {
        case 'firm':
            $heading = match ($state) {
                'unverified' => 'Verify your email to start hiring',
                'incomplete' => 'Your next great hire is waiting',
                default      => 'Start hiring on Start Your Story',
            };
            $lead = match ($state) {
                'unverified' => 'Verify your email to activate your firm account and unlock the full power of Start Your Story:',
                'incomplete' => "You're almost there — complete your firm profile to start hiring on Start Your Story:",
                default      => 'Your firm profile is ready — here is everything you can do now:',
            };
            $benefits = match ($state) {
                'unverified' => [
                    'Verify your email',
                    'Complete your firm profile',
                    'Get reviewed & approved by our team',
                    'Post jobs / content creation requests',
                    'See candidates',
                ],
                'incomplete' => [
                    'Complete your firm profile',
                    'Get reviewed & approved by our team',
                    'Post jobs / content creation requests',
                    'See candidates',
                ],
                default => [
                    'Post jobs / content creation requests',
                    'View candidates',
                    'Reach candidates via contact details',
                ],
            };
            break;

        case 'creator':
            $heading = match ($state) {
                'unverified' => 'Verify your email to get discovered',
                'incomplete' => "You're one step from more projects",
                default      => 'Get discovered for new projects',
            };
            $lead = match ($state) {
                'unverified' => 'Verify your email to activate your creator profile and make the most of Start Your Story:',
                'incomplete' => "You're almost there — complete your creator profile to make the most of Start Your Story:",
                default      => 'Your creator profile is ready — here is what is waiting for you:',
            };
            $benefits = match ($state) {
                'unverified' => [
                    'Verify your email',
                    'Complete your creator profile',
                    'Get discovered by firms',
                    'See content creation projects',
                ],
                'incomplete' => [
                    'Complete your creator profile',
                    'Get discovered by firms',
                    'See content creation projects',
                ],
                default => [
                    'Browse content creation projects',
                    'Get discovered by more firms',
                    'Win more collaborations',
                ],
            };
            break;

        default: // student
            $heading = match ($state) {
                'unverified' => 'Verify your email to start applying',
                'incomplete' => "You're almost ready to get hired",
                default      => 'Explore jobs and firms on Start Your Story',
            };
            $lead = match ($state) {
                'unverified' => 'Verify your email to activate your account and get the most out of Start Your Story:',
                'incomplete' => "You're almost there — complete your profile to get the most out of Start Your Story:",
                default      => 'Your profile is ready — here is what is waiting for you:',
            };
            $benefits = match ($state) {
                'unverified' => [
                    'Verify your email',
                    'Complete your profile',
                    'See jobs',
                    'See firms',
                ],
                'incomplete' => [
                    'Complete your profile',
                    'See jobs',
                    'See firms',
                ],
                default => [
                    'See jobs',
                    'See firms',
                ],
            };
            break;
    }
@endphp
{{--
    Re-engagement campaign (student / firm / creator × lifecycle state).
    Heading/lead/benefits are derived in the PHP block above; the mailable
    passes only name, userType, verified, profileCompleted, trackingUrl.
    Migrated from a standalone HTML shell to the shared premium layout —
    all body sections preserved.
--}}
@extends('emails.layouts.premium', [
    'title'     => $heading,
    'preheader' => $lead,
])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#475569;">

    {{-- Greeting --}}
    <p class="dm-p" style="margin:0;font-size:17px;line-height:24px;color:#334155;">
        Hello <strong class="dm-h" style="color:#0f172a;font-weight:700;">{{ $name }}</strong>,
    </p>

    {{-- Hero heading --}}
    <h1 class="dm-h h1-sm" style="margin:10px 0 0;font-size:28px;line-height:40px;font-weight:800;color:#0f172a;letter-spacing:-0.8px;">
        {{ $heading }}
    </h1>

    {{-- Lead --}}
    <p class="dm-p" style="margin:14px 0 0;font-size:16px;line-height:26px;color:#64748b;">
        {{ $lead }}
    </p>

    {{-- Motivation box --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-card" style="margin-top:22px;background-color:#eff6ff;border:1px solid #dbeafe;border-radius:12px;">
        <tr>
            <td valign="top" width="48" style="padding:20px 0 20px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" valign="middle" width="44" height="44" class="dm-icon" style="background-color:#dbeafe;border-radius:10px;font-size:20px;line-height:44px;">
                            &#128227;
                        </td>
                    </tr>
                </table>
            </td>
            <td valign="middle" style="padding:18px 22px 18px 16px;font-family:'Inter',Arial,Helvetica,sans-serif;">
                <div class="dm-h" style="font-size:16px;line-height:23px;color:#0f172a;font-weight:700;">
                    @if($userType === 'student')
                        Top firms are actively searching for candidates like you.
                    @elseif($userType === 'firm')
                        The best candidates apply to firms that are fully set up.
                    @elseif($userType === 'creator')
                        Complete profiles get more visibility and project opportunities.
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Benefits (card rows) --}}
    <p class="dm-h" style="margin:26px 0 16px;font-size:19px;line-height:25px;color:#0f172a;font-weight:700;">
        @if($state === 'unverified')
            Once verified, you can:
        @elseif($state === 'incomplete')
            Once your profile is complete, you can:
        @else
            Here is what you can do now:
        @endif
    </p>

    @php $sysIcons = ['&#128188;','&#127919;','&#128065;','&#9889;','&#9989;']; @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        @foreach($benefits as $sysIndex => $benefit)
            <tr>
                <td style="padding-bottom:{{ $loop->last ? '0' : '10px' }};">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-card" style="background-color:#f8fafc;border:1px solid #eef2f7;border-radius:12px;">
                        <tr>
                            <td valign="middle" width="56" style="padding:0 0 0 8px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td align="center" valign="middle" width="35" height="35" style="border-radius:10px;font-size:16px;line-height:38px;">
                                            {!! $sysIcons[$sysIndex % count($sysIcons)] !!}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td valign="middle" class="dm-h" style="padding:6px 18px 6px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#1e293b;font-weight:600;">
                                {{ $benefit }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        @endforeach
    </table>

    {{-- CTA — points at the signed click-tracking route, which records the
         click then redirects to the frontend /login. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0 0;">
        <tr>
            <td align="center" bgcolor="#2563eb" style="background-color:#2563eb;background-image:linear-gradient(135deg,#3b82f6,#1e40af);border-radius:12px;">
                <a href="{{ $trackingUrl }}" style="display:block;text-align:center;color:#ffffff;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:18px;font-weight:700;line-height:22px;padding:17px 24px;border-radius:12px;">
                    Login to Continue
                </a>
            </td>
        </tr>
    </table>

    {{-- Info box --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-card" style="margin:26px 0 16px;background-color:#fffbeb;border:1px solid #fde68a;border-radius:12px;">
        <tr>
            <td valign="middle" width="44" style="padding:18px 0 18px 20px;">
                <span style="font-size:24px;line-height:24px;">&#9200;</span>
            </td>
            <td valign="middle" class="dm-p" style="padding:18px 16px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#334155;">
                Finishing your setup takes <strong class="dm-h" style="color:#0f172a;">less than two minutes</strong> and makes a big difference.
            </td>
            <td valign="middle" align="right" width="60" style="padding:14px 18px 14px 0;font-size:30px;line-height:30px;">
                &#9201;&#9989;
            </td>
        </tr>
    </table>

</div>
@endsection
