{{--
    Premium 2-up card grid (premium layout).

    Renders $items as rows of two equal cards: large circular icon tile, title,
    short description. Cells in a row are the same height automatically, and each
    card becomes full width on mobile via the layout's existing `stack-card`.

    Variables:
      $items (array) each: [
          'icon'  => string  HTML entity / emoji glyph (required)
          'title' => string  (required, may contain entities)
          'desc'  => string  (required, may contain entities)
          'tint'  => string  optional icon-tile background; default light blue
          'glyph' => string  optional glyph colour (for text icons like </>)
      ]
      $gap (int) optional space below the grid in px; default 40.

    Design tokens shared with every other container in these emails:
      radius 16px · 1px border · 30px/24px padding · 16px between siblings ·
      circular icon tile (60px / 28px glyph).
--}}
@php
    $sans = "'Inter',Arial,Helvetica,sans-serif";
    $rows = array_chunk($items, 2);
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
    style="margin:0 0 {{ $gap ?? 40 }}px;">
    @foreach ($rows as $row)
        <tr>
            @foreach ($row as $i => $c)
                @if ($i > 0)
                    <td class="gap-col" width="16" style="width:16px;font-size:0;line-height:0;">&nbsp;</td>
                @endif
                <td class="stack-card dm-card" width="48%" align="center" valign="top"
                    style="width:48%;background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:16px;padding:30px 24px;">
                    {{-- align="center" as well as margin:0 auto — Outlook's Word engine
                         ignores auto margins on a nested table. --}}
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                        style="margin:0 auto;">
                        <tr>
                            <td class="dm-icon" width="60" height="60" align="center" valign="middle"
                                style="width:60px;height:60px;background-color:{{ $c['tint'] ?? '#DCE7FF' }};border-radius:30px;text-align:center;font-family:{{ $sans }};font-size:28px;font-weight:700;line-height:60px;color:{{ $c['glyph'] ?? '#1D4ED8' }};">
                                {!! $c['icon'] !!}</td>
                        </tr>
                    </table>
                    <p class="dm-h"
                        style="margin:20px 0 10px;font-family:{{ $sans }};font-size:15.5px;line-height:22px;font-weight:800;color:#0F172A;">
                        {!! $c['title'] !!}</p>
                    <p class="dm-p"
                        style="margin:0;font-family:{{ $sans }};font-size:12.5px;line-height:20px;color:#64748B;">
                        {!! $c['desc'] !!}</p>
                </td>
            @endforeach
        </tr>
        @if (!$loop->last)
            {{-- Row gutter. hide-sm because once the cards stack, `stack-card` already
                 supplies the vertical gap — without this the stacked rhythm would go
                 10px / 26px / 10px instead of an even 10px. --}}
            <tr>
                <td class="hide-sm" colspan="3" height="16" style="height:16px;font-size:0;line-height:0;">&nbsp;</td>
            </tr>
        @endif
    @endforeach
</table>
