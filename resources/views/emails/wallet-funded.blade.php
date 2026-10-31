<x-mail.layout
    :title="'Payment received'"
    :preheader="\App\Support\Site::money($payment->amount).' has been added to your wallet.'"
    eyebrow="Payment receipt"
    cta-text="Place an order"
    :cta-url="url('/new-order')">
    <p style="margin:12px 0;">Your wallet top-up was successful and the funds are available now.</p>
    @include('emails.partials.rows', ['rows' => [
        ['Reference', $payment->reference],
        ['Method', $payment->channel ? ucwords(str_replace('_', ' ', $payment->channel)).' via Paystack' : 'Paystack'],
        ['Date', ($payment->paid_at ?? $payment->created_at)->format('M j, Y · H:i')],
        ['Amount added', \App\Support\Site::money($payment->amount), true],
        ['Wallet balance now', $balance],
    ]])
</x-mail.layout>
