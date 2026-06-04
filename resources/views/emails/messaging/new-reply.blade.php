<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Message — {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="100%" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">

        <!-- Header -->
        <tr>
          <td style="background:#2563eb;padding:28px 32px;text-align:center;">
            <p style="margin:0;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.3px;">{{ $appName }}</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:32px 32px 24px;">
            <p style="margin:0 0 8px;font-size:15px;color:#111827;font-weight:600;">Hi {{ $recipientName }},</p>
            <p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
              <strong>{{ $senderName }}</strong> sent you a new message.
            </p>

            <!-- Preview box -->
            <div style="background:#f9fafb;border-left:3px solid #2563eb;border-radius:6px;padding:14px 16px;margin-bottom:24px;">
              <p style="margin:0;font-size:13px;color:#374151;line-height:1.6;font-style:italic;">"{{ $messagePreview }}"</p>
            </div>

            <p style="margin:0 0 24px;font-size:14px;color:#4b5563;line-height:1.6;">
              Log in to view and reply.
            </p>

            <!-- CTA -->
            <table cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:#2563eb;border-radius:8px;">
                  <a href="{{ $messagesUrl }}" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">
                    Reply Now
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:20px 32px;border-top:1px solid #f0f0f0;text-align:center;">
            <p style="margin:0;font-size:12px;color:#9ca3af;">© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
