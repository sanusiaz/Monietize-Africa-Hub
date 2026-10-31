<x-mail.layout
    :title="'New order #'.$order->id"
    :preheader="$order->user->name.' ordered '.number_format($order->quantity).' × '.$order->service->name.' · '.\App\Support\Site::money($order->charge)"
    eyebrow="Admin alert"
    cta-text="Review order"
    :cta-url="url('/admin/orders?search='.$order->id)">
    <p style="margin:12px 0;">A customer just placed an order and paid from their wallet.</p>
    @include('emails.partials.rows', ['rows' => [
        ['Order', '#'.$order->id],
        ['Customer', $order->user->name.' ('.$order->user->email.')'],
        ['Service', $order->service->name],
        ['Link', $order->link],
        ['Quantity', number_format($order->quantity)],
        ['Fulfilment', $order->service->provider ? 'Automatic: '.$order->service->provider->name : 'Manual (needs your action)'],
        ['Charged', \App\Support\Site::money($order->charge), true],
    ]])
    @unless ($order->service->provider)
        <p style="margin:14px 0 0;font-size:13px;color:#8a6619;"><strong>This service has no supplier linked,</strong> so the order is waiting for you to fulfil it and update its status.</p>
    @endunless
</x-mail.layout>
