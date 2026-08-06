{{--
    Centred section heading with short flanking rules (premium layout).

    Variables:
      $label (string) heading text
      $gap   (int)    optional space below the heading in px; default 26.

    The middle cell has NO fixed width and NO nowrap: the table gives it whatever
    is left after the two 72px rules, so the label stays on one line on desktop,
    and it is free to wrap on a narrow phone (where the rules are hidden) instead
    of forcing the whole email wider than the screen.
--}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
    style="margin:0 0 {{ $gap ?? 26 }}px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                <tr>
                    <td class="hide-sm" width="72" valign="middle" style="width:72px;">
                        <div class="dm-line" style="height:2px;line-height:2px;font-size:0;background-color:#BFD3FF;">
                            &nbsp;</div>
                    </td>
                    <td valign="middle" align="center" style="padding:0 16px;">
                        <span class="dm-hi"
                            style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:20px;line-height:28px;font-weight:800;color:#1D4ED8;">{{ $label }}</span>
                    </td>
                    <td class="hide-sm" width="72" valign="middle" style="width:72px;">
                        <div class="dm-line" style="height:2px;line-height:2px;font-size:0;background-color:#BFD3FF;">
                            &nbsp;</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
