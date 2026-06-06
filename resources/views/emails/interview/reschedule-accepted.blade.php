@extends('emails.layouts.app', ['heading' => 'Interview Reschedule Accepted'])

@section('content')

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    <p>
        Great news! <strong>{{ $firmName }}</strong> has accepted your proposed interview date
        for the <strong>{{ $jobTitle }}</strong> position.
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
                    Interview Date
                </td>
                <td style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $interviewDate }}
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

    <p>
        Please log in to your Start Your Story account to confirm or update your availability for this interview.
    </p>

    <div class="info-box" style="margin-top: 20px;">
        <p>
            You can manage your interview from the <strong>My Jobs</strong> page on Start Your Story.
        </p>
    </div>

@endsection
