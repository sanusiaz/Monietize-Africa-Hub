<x-mail.layout
    :title="'Order #'.$order->id.' received'"
    :preheader="'We have your order for '.$order->service->name.' and it is now being processed.'"
    eyebrow="Order confirmation"
    cta-text="Track your order"
    :cta-url="url('/my-orders')">
    <p style="margin:12px 0;">Hi {{ explode(' ', $order->user->name)[0] }}, thank you. Your order has been placed and the amount was taken from your wallet.</p>
    @include('emails.partials.rows', ['rows' => [
        ['Order', '#'.$order->id],
        ['Service', $order->service->name],
        ['Link', $order->link],
        ['Quantity', number_format($order->quantity)],
        ['Status', 'Pending'],
        ['Total charged', \App\Support\Site::money($order->charge), true],
        ['Wallet balance now', $balance],
    ]])
    <p class="t-muted" style="margin:14px 0 0;font-size:13px;color:#7a7888;">We will email you again when this order is complete. Delivery speed varies by service.</p>
</x-mail.layout>
