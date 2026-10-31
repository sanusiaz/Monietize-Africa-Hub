<?php

namespace App\Support;

/** Home-page questions, shown on the page and published as FAQ structured data. */
class Faq
{
    public static function items(): array
    {
        return [
            ['q' => 'How do I pay for an order?', 'a' => 'You add money to your wallet with Paystack, then each order is paid from that balance. There are no subscriptions.'],
            ['q' => 'How is the price worked out?', 'a' => 'Every service is priced per 1,000. Enter a quantity and the exact total is shown before you place the order.'],
            ['q' => 'What happens if an order is cancelled or only partly delivered?', 'a' => 'The amount for anything not delivered goes back to your wallet automatically.'],
            ['q' => 'Do you need my password?', 'a' => 'No. You only give us the link to a public profile or post.'],
            ['q' => 'How fast does delivery start?', 'a' => 'Orders are sent to the supplier as soon as they are paid for. Speed depends on the service, and you can follow progress in your dashboard.'],
        ];
    }
}
