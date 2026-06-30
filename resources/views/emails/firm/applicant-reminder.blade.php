@extends('emails.layouts.app', ['heading' => 'Applicants Awaiting Review'])

@section('content')

    <p>
        Hello <strong>{{ $firmName }}</strong>,
    </p>

    <p>
        You have
        <strong>{{ $totalCount }} {{ $totalCount === 1 ? 'applicant' : 'applicants' }}</strong>
        waiting for your review across {{ count($jobs) === 1 ? 'this job posting' : 'these job postings' }}:
    </p>

    {{-- Jobs needing review --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="
        border-collapse: collapse;
        margin: 24px 0;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    ">
        <tr style="background: #1e3a8a;">
            <th style="padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #ffffff; border-bottom: 1px solid #1e40af;">
                Job Posting
            </th>
            <th style="padding: 12px 16px; text-align: right; font-size: 13px; font-weight: 600; color: #ffffff; border-bottom: 1px solid #1e40af;">
                Pending Applicants
            </th>
        </tr>
        @foreach ($jobs as $index => $job)
            <tr style="background: {{ $index % 2 === 0 ? '#ffffff' : '#f9fafb' }};">
                <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; font-weight: 600;">
                    {{ $job['title'] }}
                </td>
                <td style="padding: 12px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #e5e7eb; text-align: right;">
                    {{ $job['count'] }}
                </td>
            </tr>
        @endforeach
    </table>

    <p style="text-align: center; margin: 28px 0 8px;">
        @include('emails.partials.cta-button', [
            'url'   => $viewUrl,
            'label' => 'Review Applicants',
            'color' => 'primary',
        ])
    </p>

    <div class="info-box" style="margin-top: 20px;">
        <p>
            Reviewing applicants promptly helps you secure the best candidates before they accept
            other offers. Log in to shortlist, schedule interviews, or decline.
        </p>
    </div>

@endsection
