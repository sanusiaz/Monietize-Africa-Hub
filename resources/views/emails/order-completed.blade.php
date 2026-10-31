<x-mail.layout
    :title="'Order #'.$order->id.' is complete'"
    :preheader="'Your '.$order->service->name.' order has been delivered.'"
    eyebrow="Order completed"
    cta-text="Place another order"
    :cta-url="url('/new-order')">
    <p style="margin:12px 0;">Good news, {{ explode(" ", $order->user->name)[0] }}. Your order has been marked as completed.</p>
    @include('emails.partials.rows', ['rows' => [
        ['Order', '#'.$order->id],
        ['Service', $order->service->name],
        ['Link', $order->link],
        ['Quantity', number_format($order->quantity)],
        ['Status', 'Completed'],
        ['Total', \App\Support\Site::money($order->charge), true],
    ]])
    <p class="t-muted" style="margin:14px 0 0;font-size:13px;color:#7a7888;">Results can take a little time to appear on the platform. If something does not look right, just reply to this email with your order number.</p>
</x-mail.layout>
