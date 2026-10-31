@props(['title', 'preheader' => '', 'ctaText' => null, 'ctaUrl' => null, 'eyebrow' => null])
@php
    $site = \App\Support\Site::profile();
    $privacy = \App\Models\Page::where('slug', 'privacy-policy')->where('is_published', true)->exists();
    $terms = \App\Models\Page::where('slug', 'terms-and-conditions')->where('is_published', true)->exists();
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; }
        a { color: #8a6619; }
        @media (max-width: 620px) {
            .container { width: 100% !important; }
            .px { padding-left: 22px !important; padding-right: 22px !important; }
            .h1 { font-size: 26px !important; }
        }
        @media (prefers-color-scheme: dark) {
            .bg-page { background-color: #0b0b10 !important; }
            .bg-card { background-color: #15151d !important; border-color: #2a2a36 !important; }
            .t-main { color: #f5efe3 !important; }
            .t-muted { color: #a3a3b3 !important; }
            .bg-soft { background-color: #1c1c26 !important; border-color: #2a2a36 !important; }
            .rule { border-color: #2a2a36 !important; }
            a { color: #e3c98a !important; }
            .btn a { color: #07070a !important; }
        }
    </style>
</head>
<body class="bg-page" style="margin:0;padding:0;background-color:#f4f1ea;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader }}&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="bg-page" style="background-color:#f4f1ea;">
        <tr>
            <td align="center" style="padding:32px 14px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">
                    {{-- brand --}}
                    <tr>
                        <td align="center" style="padding:0 0 22px;">
                            @php($words = preg_split('/\s+/', trim($site['name'])))
                            <a href="{{ url('/') }}" style="text-decoration:none;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="padding-right:10px;"><img src="{{ url('/images/icon-192.png') }}" width="36" height="36" alt="" style="display:block;border:0;border-radius:9px;"></td>
                                    <td class="t-main" style="font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;font-size:22px;font-weight:800;color:#1c1b24;letter-spacing:-0.4px;">{{ count($words) > 1 ? implode(' ', array_slice($words, 0, -1)) : $words[0] }}@if (count($words) > 1) <span style="color:#a9843f;">{{ end($words) }}</span>@endif</td>
                                </tr></table>
                            </a>
                        </td>
                    </tr>
                    {{-- card --}}
                    <tr>
                        <td class="bg-card" style="background-color:#ffffff;border:1px solid #e8e1d0;border-radius:16px;overflow:hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="height:5px;background-color:#c9a45c;font-size:0;line-height:0;">&nbsp;</td></tr>
                                <tr>
                                    <td class="px" style="padding:36px 40px 12px;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;">
                                        @if ($eyebrow)
                                            <p style="margin:0 0 10px;font-size:11px;font-weight:600;letter-spacing:2.2px;text-transform:uppercase;color:#9a7630;">{{ $eyebrow }}</p>
                                        @endif
                                        <h1 class="h1 t-main" style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:32px;line-height:1.15;font-weight:bold;color:#1c1b24;">{{ $title }}</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px t-main" style="padding:8px 40px 4px;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#2c2b36;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                                @if ($ctaText && $ctaUrl)
                                    <tr>
                                        <td class="px" style="padding:20px 40px 8px;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                                <td class="btn" style="background-color:#c9a45c;border-radius:10px;">
                                                    <a href="{{ $ctaUrl }}" style="display:inline-block;padding:13px 26px;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;color:#07070a;text-decoration:none;">{{ $ctaText }}</a>
                                                </td>
                                            </tr></table>
                                        </td>
                                    </tr>
                                @endif
                                <tr><td style="height:30px;font-size:0;line-height:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>
                    {{-- footer --}}
                    <tr>
                        <td align="center" class="px t-muted" style="padding:24px 24px 0;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#7a7888;">
                            @if ($site['support_email'])
                                Questions? Reply to this email or write to <a href="mailto:{{ $site['support_email'] }}">{{ $site['support_email'] }}</a>.<br>
                            @endif
                            @if ($privacy || $terms)
                                @if ($terms)<a href="{{ url('/terms-and-conditions') }}">Terms &amp; Conditions</a>@endif
                                @if ($privacy && $terms) &nbsp;·&nbsp; @endif
                                @if ($privacy)<a href="{{ url('/privacy-policy') }}">Privacy Policy</a>@endif
                                <br>
                            @endif
                            &copy; {{ date('Y') }} {{ $site['company_name'] }}.@if ($site['address']) {{ $site['address'] }}.@endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
