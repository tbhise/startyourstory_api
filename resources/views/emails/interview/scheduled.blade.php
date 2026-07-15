@extends('emails.layouts.premium', ['title' => 'Interview Request'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    @if (!empty($jobTitle))
        <p>
            <strong>{{ $firmName }}</strong> has requested an interview with you for the
            <strong>{{ $jobTitle }}</strong> position. Please review the details below and confirm
            your availability.
        </p>
    @else
        <p>
            <strong>{{ $firmName }}</strong> has requested an interview with you. Please review
            the details below and confirm your availability.
        </p>
    @endif

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
            @if (!empty($jobTitle))
                <tr>
                    <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                        Position
                    </td>
                    <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                        {{ $jobTitle }}
                    </td>
                </tr>
            @endif
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
            @if (!empty($interviewLocation ?? null))
                <tr>
                    <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                        {{ strtolower($interviewMode) === 'online' ? 'Meeting Link' : 'Location' }}
                    </td>
                    <td class="dm-p" style="padding: 6px 0; font-size: 15px; color: #374151; word-break: break-all;">
                        {{ $interviewLocation }}
                    </td>
                </tr>
            @endif
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

    <p style="text-align: center; margin: 8px 0 4px;">
        Please respond to confirm your availability from your {{ $respondPage ?? 'My Jobs' }} page:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 20px 0;">
        <tr>
            <td align="center" style="padding: 6px;">
                @include('emails.partials.cta-button', [
                    'url'   => $respondUrl ?? (config('app.url') . '/my-jobs'),
                    'label' => $respondLabel ?? 'View in My Jobs',
                    'color' => 'primary',
                ])
            </td>
        </tr>
    </table>

    <div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7; margin-top: 20px;">
        @if (!empty($respondHint ?? null))
            <p>{{ $respondHint }}</p>
        @else
            <p>
                Open the <strong>Applied</strong> tab in My Jobs, find this position, and use the
                Accept, Decline, or Request Reschedule buttons to respond.
            </p>
        @endif
    </div>

</div>
@endsection
