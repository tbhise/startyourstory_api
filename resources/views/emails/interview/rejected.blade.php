@extends('emails.layouts.app', ['heading' => 'Interview Declined'])

@section('content')

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

@endsection
