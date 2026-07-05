@extends('emails.layouts.premium', ['title' => 'Applicants Awaiting Review'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

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
            <tr class="{{ $index % 2 === 0 ? 'dm-row-a' : 'dm-row-b' }}" style="background: {{ $index % 2 === 0 ? '#ffffff' : '#f9fafb' }};">
                <td class="dm-h" style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; font-weight: 600;">
                    {{ $job['title'] }}
                </td>
                <td class="dm-p" style="padding: 12px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #e5e7eb; text-align: right;">
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

    <div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7; margin-top: 20px;">
        <p>
            Reviewing applicants promptly helps you secure the best candidates before they accept
            other offers. Log in to shortlist, schedule interviews, or decline.
        </p>
    </div>

</div>
@endsection
