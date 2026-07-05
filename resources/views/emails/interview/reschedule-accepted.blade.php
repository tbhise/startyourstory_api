@extends('emails.layouts.premium', ['title' => 'Interview Reschedule Accepted'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    <p>
        Great news! <strong>{{ $firmName }}</strong> has accepted your proposed interview date
        for the <strong>{{ $jobTitle }}</strong> position.
    </p>

    {{-- Interview Detail Card --}}
    <div class="dm-card" style="
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
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
                    Interview Date
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $interviewDate }}
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

    <p>
        Please log in to your Start Your Story account to confirm or update your availability for this interview.
    </p>

    <div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7; margin-top: 20px;">
        <p>
            You can manage your interview from the <strong>My Jobs</strong> page on Start Your Story.
        </p>
    </div>

</div>
@endsection
