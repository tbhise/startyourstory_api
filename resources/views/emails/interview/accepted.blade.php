@extends('emails.layouts.premium', ['title' => 'Interview Confirmed'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    @if (!empty($jobTitle))
        <p>
            Good news! <strong>{{ $candidateName }}</strong> has <strong>accepted</strong> your
            interview request for the <strong>{{ $jobTitle }}</strong> position.
            The interview will proceed as scheduled.
        </p>
    @else
        <p>
            Good news! <strong>{{ $candidateName }}</strong> has <strong>accepted</strong> your
            interview request. The interview will proceed as scheduled.
        </p>
    @endif

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
                    Candidate
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $candidateName }}
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
        </table>
    </div>

    <p style="text-align: center; margin: 28px 0 8px;">
        @include('emails.partials.cta-button', [
            'url'   => $viewApplicationsUrl,
            'label' => $ctaLabel ?? 'View Applications',
            'color' => 'primary',
        ])
    </p>

</div>
@endsection
