@extends('emails.layouts.app', ['heading' => 'Interview Response Pending'])

@section('content')

    <p>
        Hello <strong>{{ $candidateName }}</strong>,
    </p>

    <p>
        <strong>{{ $firmName }}</strong> is waiting for your response to an interview invitation.
        Please accept or reject the interview request so {{ $firmName }} knows how to proceed.
    </p>

    @if ($isFinal)
        <div style="
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            color: #991b1b;
            font-size: 15px;
        ">
            This is a final reminder. If you don't respond soon, the firm may withdraw the invitation.
        </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 24px 0;">
        <tr>
            <td align="center" style="padding: 6px;">
                @include('emails.partials.cta-button', [
                    'url'   => $respondUrl,
                    'label' => 'Accept or Reject Invitation',
                    'color' => 'primary',
                ])
            </td>
        </tr>
    </table>

    <div class="info-box" style="margin-top: 20px;">
        <p>
            Open your <strong>Notifications</strong> to respond. If you accept, {{ $firmName }}
            will share the interview schedule with you next.
        </p>
    </div>

@endsection
