@extends('emails.layouts.app', ['heading' => 'Interview Invitation'])

@section('content')

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    <p>
        <strong>{{ $firmName }}</strong> has invited you for an interview. Let them know whether
        you'd like to proceed — you can accept or decline this invitation from your account.
    </p>

    @if (!empty($inviteMessage))
        <div style="
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            color: #374151;
            font-size: 15px;
        ">
            {{ $inviteMessage }}
        </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 20px 0;">
        <tr>
            <td align="center" style="padding: 6px;">
                @include('emails.partials.cta-button', [
                    'url'   => $respondUrl,
                    'label' => 'View Invitation',
                    'color' => 'primary',
                ])
            </td>
        </tr>
    </table>

    <div class="info-box" style="margin-top: 20px;">
        <p>
            Open your <strong>Notifications</strong> to accept or decline. If you accept,
            {{ $firmName }} will share the interview schedule with you next.
        </p>
    </div>

@endsection
