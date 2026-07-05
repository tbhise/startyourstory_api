@extends('emails.layouts.premium', ['title' => 'Interview Declined'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    <p>
        <strong>{{ $candidateName }}</strong> has <strong>declined</strong> the interview request
        for the <strong>{{ $jobTitle }}</strong> position.
    </p>

    <p>
        You may consider reviewing other applicants or reaching out to shortlisted candidates
        from your applications dashboard.
    </p>

    <p style="text-align: center; margin: 28px 0 8px;">
        @include('emails.partials.cta-button', [
            'url'   => $viewApplicationsUrl,
            'label' => 'View Other Applicants',
            'color' => 'primary',
        ])
    </p>

</div>
@endsection
