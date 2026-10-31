<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderCompletedMail;
use App\Mail\OrderPlacedMail;
use App\Mail\TestMail;
use App\Mail\WalletFundedMail;
use App\Mail\WelcomeMail;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

/** Renders each email with sample data so the admin can see exactly what customers receive. */
class EmailPreviewController extends Controller
{
    public const TYPES = [
        'welcome' => 'Welcome (customer)',
        'order-placed' => 'Order confirmation (customer)',
        'new-order' => 'New order alert (admin)',
        'order-completed' => 'Order completed (customer)',
        'wallet-funded' => 'Payment receipt (customer)',
        'contact' => 'Contact message (admin)',
        'test' => 'Test email',
    ];

    public function __invoke(Request $request, string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $user = new User(['name' => 'Ada Okafor', 'email' => 'ada@example.com', 'balance' => 18500]);
        $service = new Service(['name' => 'Instagram Followers - Premium (Refill)']);
        $service->setRelation('provider', null);
        $order = new Order(['link' => 'https://instagram.com/adaokafor', 'quantity' => 2500, 'charge' => 8000]);
        $order->id = 1042;
        $order->setRelation('user', $user)->setRelation('service', $service);

        $payment = new Payment(['reference' => 'MH-7K2QX9ZP4TLA1B', 'amount' => 20000, 'channel' => 'card', 'paid_at' => now()]);
        $payment->setRelation('user', $user);

        $contact = new ContactMessage(['name' => 'Ada Okafor', 'email' => 'ada@example.com', 'subject' => 'Question about refills', 'message' => "Hello,\n\nDo your refill services cover followers who drop after a week?\n\nThanks!"]);
        $contact->created_at = now();

        $mail = match ($type) {
            'welcome' => new WelcomeMail($user),
            'order-placed' => new OrderPlacedMail($order),
            'new-order' => new NewOrderAdminMail($order),
            'order-completed' => new OrderCompletedMail($order),
            'wallet-funded' => new WalletFundedMail($payment),
            'contact' => new ContactMessageMail($contact),
            'test' => new TestMail,
        };

        return response($mail->render());
    }
}
