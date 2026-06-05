@extends('emails.layouts.app', ['heading' => 'Interview Confirmed'])

@section('content')

    <p>
        Good news! <strong>{{ $candidateName }}</strong> has <strong>accepted</strong> your
        interview request for the <strong>{{ $jobTitle }}</strong> position.
    </p>

    {{-- Interview Detail Card --}}
    <div style="
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 20px 24px;
        margin: 24px 0;
    ">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 6px 0; font-size: 14px; color: #6b7280; width: 120px; vertical-align: top;">
                    Candidate
                </td>
                <td style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $candidateName }}
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
        </table>
    </div>

    <p style="text-align: center; margin: 28px 0 8px;">
        @include('emails.partials.cta-button', [
            'url'   => $viewApplicationsUrl,
            'label' => 'View Applications',
            'color' => 'primary',
        ])
    </p>

@endsection
