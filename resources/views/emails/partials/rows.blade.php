{{-- $rows: list of [label, value, (bool) emphasise] --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="bg-soft" style="margin:18px 0 6px;background-color:#faf7f0;border:1px solid #ece5d3;border-radius:12px;">
    @foreach ($rows as $i => $row)
        <tr>
            <td class="t-muted rule" style="padding:12px 18px;font-size:13px;color:#7a7888;width:38%;vertical-align:top;@if (! $loop->last) border-bottom:1px solid #ece5d3; @endif">{{ $row[0] }}</td>
            <td class="t-main rule" style="padding:12px 18px;font-size:14px;color:#1c1b24;text-align:right;word-break:break-word;vertical-align:top;@if (! empty($row[2])) font-weight:700;font-size:16px; @endif @if (! $loop->last) border-bottom:1px solid #ece5d3; @endif">{{ $row[1] }}</td>
        </tr>
    @endforeach
</table>
