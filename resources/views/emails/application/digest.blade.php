@extends('emails.layouts.app', ['heading' => 'New Applications'])

@section('content')

    <p>
        Hello <strong>{{ $firmName }}</strong>,
    </p>

    <p>
        You have received
        <strong>{{ $applicationCount }} new {{ $applicationCount === 1 ? 'application' : 'applications' }}</strong>
        in the last hour. Here is a summary:
    </p>

    {{-- Applications Table --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="
        border-collapse: collapse;
        margin: 24px 0;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    ">
        {{-- Header Row --}}
        <tr style="background: #1e3a8a;">
            <th style="
                padding: 12px 16px;
                text-align: left;
                font-size: 13px;
                font-weight: 600;
                color: #ffffff;
                border-bottom: 1px solid #1e40af;
            ">
                Candidate
            </th>
            <th style="
                padding: 12px 16px;
                text-align: left;
                font-size: 13px;
                font-weight: 600;
                color: #ffffff;
                border-bottom: 1px solid #1e40af;
            ">
                Applied For
            </th>
            <th style="
                padding: 12px 16px;
                text-align: left;
                font-size: 13px;
                font-weight: 600;
                color: #ffffff;
                border-bottom: 1px solid #1e40af;
            ">
                Applied At
            </th>
        </tr>

        {{-- Application Rows --}}
        @foreach ($applications as $index => $application)
            <tr style="background: {{ $index % 2 === 0 ? '#ffffff' : '#f9fafb' }};">
                <td style="
                    padding: 12px 16px;
                    font-size: 14px;
                    color: #111827;
                    border-bottom: 1px solid #e5e7eb;
                    font-weight: 600;
                ">
                    {{ $application['candidate_name'] }}
                </td>
                <td style="
                    padding: 12px 16px;
                    font-size: 14px;
                    color: #374151;
                    border-bottom: 1px solid #e5e7eb;
                ">
                    {{ $application['job_title'] }}
                </td>
                <td style="
                    padding: 12px 16px;
                    font-size: 13px;
                    color: #6b7280;
                    border-bottom: 1px solid #e5e7eb;
                ">
                    {{ $application['applied_at'] }}
                </td>
            </tr>
        @endforeach
    </table>

    <p style="text-align: center; margin: 28px 0 8px;">
        @include('emails.partials.cta-button', [
            'url'   => $viewApplicationsUrl,
            'label' => 'Review All Applications',
            'color' => 'primary',
        ])
    </p>

    <div class="info-box" style="margin-top: 20px;">
        <p>
            You receive this digest hourly when new applications arrive.
            Log in to your dashboard to review, shortlist, and schedule interviews.
        </p>
    </div>

@endsection
