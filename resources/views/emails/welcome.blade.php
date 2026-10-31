<x-mail.layout
    :title="'Welcome, '.explode(' ', $user->name)[0].'.'"
    :preheader="'Your '.$site.' account is ready. Fund your wallet and place your first order.'"
    eyebrow="Account created"
    cta-text="Open your dashboard"
    :cta-url="url('/account')">
    <p style="margin:12px 0;">Thanks for joining <strong>{{ $site }}</strong>. Your account is ready, and getting started takes three quick steps:</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0 4px;">
        @foreach ([['1', 'Fund your wallet', 'Top up securely with Paystack. Your balance is available as soon as the payment clears.'], ['2', 'Choose a service', 'Pick a platform, paste your profile or post link, and choose a quantity. The total is shown before you order.'], ['3', 'Track delivery', 'Follow each order from pending to completed in your dashboard.']] as $step)
            <tr>
                <td valign="top" style="width:30px;padding:6px 0;"><span style="display:inline-block;width:24px;height:24px;border-radius:12px;background-color:#c9a45c;color:#07070a;text-align:center;font-size:12px;font-weight:700;line-height:24px;">{{ $step[0] }}</span></td>
                <td style="padding:6px 0 10px;"><strong>{{ $step[1] }}</strong><br><span class="t-muted" style="color:#7a7888;font-size:14px;">{{ $step[2] }}</span></td>
            </tr>
        @endforeach
    </table>
    <p class="t-muted" style="margin:14px 0 0;font-size:13px;color:#7a7888;">You signed up with <strong>{{ $user->email }}</strong>. We will never ask for your password by email.</p>
</x-mail.layout>
