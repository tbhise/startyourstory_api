{{--
    Reusable CTA button partial.
    Variables:
      $url   (string)
      $label (string)
      $color (optional, default 'primary') : primary (#2563eb) | success (#16a34a) | danger (#dc2626)
      $size  (optional, default 'md')       : 'lg' for the dominant primary CTA, 'md' otherwise
--}}
@php
    $bgMap = [
        'primary' => '#2563eb',
        'success' => '#16a34a',
        'danger' => '#dc2626',
    ];
    $shadowMap = [
        'primary' => '0 6px 16px rgba(37, 99, 235, 0.30)',
        'success' => '0 6px 16px rgba(22, 163, 74, 0.30)',
        'danger' => '0 6px 16px rgba(220, 38, 38, 0.30)',
    ];
    $key   = $color ?? 'primary';
    $bg    = $bgMap[$key] ?? '#2563eb';
    $isLg  = ($size ?? 'md') === 'lg';

    $padding  = $isLg ? '17px 44px' : '13px 30px';
    $fontSize = $isLg ? '17px' : '15px';
    $radius   = $isLg ? '12px' : '10px';
    $shadow   = $isLg ? ($shadowMap[$key] ?? $shadowMap['primary']) : 'none';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
    <tr>
        <td align="center" style="border-radius: {{ $radius }}; background: {{ $bg }}; box-shadow: {{ $shadow }};">
            <a href="{{ $url }}" target="_blank"
                style="
                   display: inline-block;
                   padding: {{ $padding }};
                   color: #ffffff;
                   font-size: {{ $fontSize }};
                   font-weight: 700;
                   text-decoration: none;
                   border-radius: {{ $radius }};
                   font-family: Arial, Helvetica, sans-serif;
                   letter-spacing: 0.2px;
               ">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
