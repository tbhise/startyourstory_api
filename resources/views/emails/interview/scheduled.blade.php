@extends('emails.layouts.app', ['heading' => 'Interview Request'])

@section('content')

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    <p>
        <strong>{{ $firmName }}</strong> has requested an interview with you for the
        <strong>{{ $jobTitle }}</strong> position. Please review the details below and confirm
        your availability.
    </p>

    {{-- Interview Detail Card --}}
    <div style="
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 20px 24px;
        margin: 24px 0;
    ">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 6px 0; font-size: 14px; color: #6b7280; width: 120px; vertical-align: top;">
                    Company
                </td>
                <td style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $firmName }}
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Position
                </td>
                <td style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $jobTitle }}
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Date & Time
                </td>
                <td style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $interviewDate }}
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Mode
                </td>
                <td style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $interviewMode }}
                </td>
            </tr>
            @if (!empty($interviewNote))
                <tr>
                    <td style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                        Note
                    </td>
                    <td style="padding: 6px 0; font-size: 15px; color: #374151;">
                        {{ $interviewNote }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <p style="text-align: center; margin: 8px 0 4px;">
        Please respond to confirm your availability from your My Jobs page:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 20px 0;">
        <tr>
            <td align="center" style="padding: 6px;">
                @include('emails.partials.cta-button', [
                    'url'   => config('app.url') . '/my-jobs',
                    'label' => 'View in My Jobs',
                    'color' => 'primary',
                ])
            </td>
        </tr>
    </table>

    <div class="info-box" style="margin-top: 20px;">
        <p>
            Open the <strong>Applied</strong> tab in My Jobs, find this position, and use the
            Accept, Decline, or Request Reschedule buttons to respond.
        </p>
    </div>

@endsection
