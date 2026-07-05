{{--
    Shared template for both 24-hour and 1-hour interview reminders.
    Variable $hoursAway: 24 or 1
--}}
@php
    $timeLabel  = $hoursAway === 1 ? 'in 1 hour' : 'tomorrow';
    $urgency    = $hoursAway === 1 ? 'Starting Soon' : 'Tomorrow';
    $badgeBg    = $hoursAway === 1 ? '#dc2626' : '#d97706';
    $heading    = "Interview Reminder — {$urgency}";
@endphp

@extends('emails.layouts.premium', ['title' => $heading])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    <p>
        This is a friendly reminder that your interview with
        <strong>{{ $firmName }}</strong> for the <strong>{{ $jobTitle }}</strong>
        position is scheduled <strong>{{ $timeLabel }}</strong>.
    </p>

    {{-- Urgency Badge --}}
    <div style="text-align: center; margin: 16px 0;">
        <span style="
            display: inline-block;
            background: {{ $badgeBg }};
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 20px;
        ">
            {{ $hoursAway === 1 ? 'Starting in 1 Hour' : 'Tomorrow' }}
        </span>
    </div>

    {{-- Interview Detail Card --}}
    <div class="dm-card" style="
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 20px 24px;
        margin: 24px 0;
    ">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; width: 120px; vertical-align: top;">
                    Company
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $firmName }}
                </td>
            </tr>
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Position
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $jobTitle }}
                </td>
            </tr>
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Date & Time
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $interviewDate }}
                </td>
            </tr>
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Mode
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $interviewMode }}
                </td>
            </tr>
            @if (!empty($interviewNote))
                <tr>
                    <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                        Note
                    </td>
                    <td class="dm-p" style="padding: 6px 0; font-size: 15px; color: #374151;">
                        {{ $interviewNote }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <p style="text-align: center; margin: 20px 0 8px;">
        @include('emails.partials.cta-button', [
            'url'   => $viewDetailsUrl,
            'label' => 'View Interview Details',
            'color' => 'primary',
        ])
    </p>

    <div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7; margin-top: 20px;">
        <p>
            Best of luck with your interview!
            Make sure you are prepared and available at the scheduled time.
        </p>
    </div>

</div>
@endsection
