{{--
    Reusable CTA button partial.
    Variables: $url (string), $label (string), $color (optional, default 'primary')
    Colors: primary (#2563eb) | success (#16a34a) | danger (#dc2626)
--}}
@php
    $bgMap = [
        'primary' => '#2563eb',
        'success' => '#16a34a',
        'danger' => '#dc2626',
    ];
    $bg = $bgMap[$color ?? 'primary'] ?? '#2563eb';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
    <tr>
        <td align="center" style="border-radius: 10px; background: {{ $bg }};">
            <a href="{{ $url }}" target="_blank"
                style="
                   display: inline-block;
                   padding: 14px 28px;
                   color: #ffffff;
                   font-size: 15px;
                   font-weight: 600;
                   text-decoration: none;
                   border-radius: 10px;
                   font-family: Arial, Helvetica, sans-serif;
               ">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
