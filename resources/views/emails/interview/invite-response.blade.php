@extends('emails.layouts.premium', ['title' => 'Interview Invitation Update'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    <p>
        Hello <strong>{{ $firmName }}</strong>,
    </p>

    @if ($accepted)
        <p>
            Good news — <strong>{{ $candidateName }}</strong> has
            <strong style="color: #047857;">accepted</strong> your interview invitation.
            You can now schedule the interview from the candidate's profile.
        </p>
    @else
        <p>
            <strong>{{ $candidateName }}</strong> has
            <strong style="color: #b91c1c;">declined</strong> your interview invitation.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 20px 0;">
        <tr>
            <td align="center" style="padding: 6px;">
                @include('emails.partials.cta-button', [
                    'url'   => $viewUrl,
                    'label' => $accepted ? 'Schedule Interview' : 'View Candidates',
                    'color' => 'primary',
                ])
            </td>
        </tr>
    </table>

</div>
@endsection
