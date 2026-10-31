<?php

namespace App\Services;

use App\Mail\ContactMessageMail;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderCompletedMail;
use App\Mail\OrderPlacedMail;
use App\Mail\WalletFundedMail;
use App\Mail\WelcomeMail;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Every transactional email goes through here.
 * Mail is sent after the HTTP response, and a mail failure is logged, never thrown,
 * so a broken SMTP server can never block a signup, an order or an admin action.
 */
class Notifier
{
    /** The admin notification address from Settings, else every admin account. */
    public function adminRecipients(): array
    {
        $configured = Setting::get('admin_email');

        return $configured ? [$configured] : User::where('role', 'admin')->where('is_active', true)->pluck('email')->all();
    }

    public function welcome(User $user): void
    {
        $this->send($user->email, fn () => new WelcomeMail($user));
    }

    public function orderPlaced(Order $order): void
    {
        $this->send($order->user->email, fn () => new OrderPlacedMail($order));
        $this->send($this->adminRecipients(), fn () => new NewOrderAdminMail($order));
    }

    public function orderCompleted(Order $order): void
    {
        $this->send($order->user->email, fn () => new OrderCompletedMail($order));
    }

    public function walletFunded(Payment $payment): void
    {
        $this->send($payment->user->email, fn () => new WalletFundedMail($payment));
    }

    public function contactReceived(ContactMessage $message): void
    {
        $this->send($this->adminRecipients(), fn () => new ContactMessageMail($message));
    }

    /** @param  string|array<int, string>  $to  @param  \Closure(): Mailable  $mailable */
    private function send(string|array $to, \Closure $mailable): void
    {
        if (empty($to)) {
            return;
        }

        defer(function () use ($to, $mailable) {
            try {
                Mail::to($to)->send($mailable());
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
