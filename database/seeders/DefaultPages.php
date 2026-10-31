<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starter legal pages written for how this site actually works.
 * Placeholders such as {{site_name}} are filled from Admin → Settings when the page is shown.
 * They are a starting point, not legal advice: have a lawyer review them before launch.
 */
class DefaultPages extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(['slug' => 'privacy-policy'], [
            'title' => 'Privacy Policy',
            'meta_description' => 'How {{site_name}} collects, uses and protects your personal information.',
            'sort_order' => 1,
            'content' => $this->privacy(),
        ]);

        Page::firstOrCreate(['slug' => 'terms-and-conditions'], [
            'title' => 'Terms & Conditions',
            'meta_description' => 'The terms that apply when you use {{site_name}}.',
            'sort_order' => 2,
            'content' => $this->terms(),
        ]);
    }

    private function privacy(): string
    {
        return <<<'MD'
*Last updated: {{updated_at}}*

This Privacy Policy explains what personal information {{company_name}} ("we", "us", "our") collects when you use {{site_name}}, how we use it, who we share it with, and the choices you have. By creating an account or using the website you agree to the practices described here.

## 1. Information we collect

**Account information.** When you register we collect your name, email address and a password. Your password is stored only in encrypted (hashed) form; we cannot read it.

**Order information.** When you place an order we record the service you chose, the profile or post link you supplied, the quantity, the amount charged, and the order's status and history.

**Wallet and payment information.** We keep a ledger of every credit and debit on your wallet. When you add funds, payment is processed by our payment provider, Paystack. We receive the payment reference, amount, status and payment channel (for example card or bank transfer). **We do not receive or store your full card number, PIN or CVV.**

**Messages you send us.** If you use the contact form we store your name, email address and the contents of your message so we can reply.

**Technical information.** To keep your session secure and to protect the site from abuse, our systems record your IP address and browser type (user agent) when you sign in or interact with the site.

## 2. How we use your information

We use your information to:

- create and manage your account and wallet;
- process and deliver your orders, and send them to the suppliers who fulfil them;
- take payments, issue refunds and keep accurate financial records;
- send you service emails such as your welcome message, order confirmations, order-completed notices and payment receipts;
- respond to your questions and support requests;
- detect, prevent and investigate fraud, abuse and security incidents; and
- comply with legal and accounting obligations.

We do not sell your personal information.

## 3. Who we share it with

We share information only where it is needed to run the service:

- **Paystack**, to process wallet top-ups.
- **Fulfilment suppliers**, who receive the link and quantity for the order they deliver. We do not give suppliers your name, email or password.
- **Our email delivery provider**, to send you the service emails described above.
- **Authorities and advisers**, where we are legally required to, or where necessary to protect our rights, our customers or the public.

These providers may process data in countries other than your own. We only share what is needed for them to perform their service.

## 4. Cookies and similar technologies

We use only what is necessary for the site to work:

- a **session cookie** that keeps you signed in;
- a **security (CSRF) cookie** that protects forms from forgery;
- your **theme preference** (light or dark), saved in your browser's local storage; and
- if **live chat** is switched on, cookies or local storage set by our chat provider so your conversation can continue.

We do not use advertising cookies.

## 5. How long we keep information

We keep your account, order and wallet records for as long as your account is open and afterwards for as long as needed to meet legal, tax, accounting and dispute-resolution requirements. Contact-form messages are kept for as long as needed to handle your enquiry. When information is no longer needed we delete it or make it anonymous.

## 6. Security

We protect your information with measures including encrypted connections, hashed passwords, access controls for administrators, and secure handling of payment data by Paystack. No system is completely secure, so please use a strong, unique password and keep it private.

## 7. Your rights

Depending on where you live, you may have the right to access the personal information we hold about you, correct it, delete it, object to or restrict certain uses, and receive a copy of it. To exercise any of these rights, contact us using the details below. We may need to verify your identity first, and we may keep information we are legally required to retain.

## 8. Children

{{site_name}} is intended for people aged 18 and over. We do not knowingly collect information from anyone under 18. If you believe a child has given us information, contact us and we will delete it.

## 9. Changes to this policy

We may update this policy from time to time. The "Last updated" date above shows when it last changed. If we make significant changes we will take reasonable steps to let you know, for example by email or a notice on the site.

## 10. Contact us

If you have questions about this policy or how we handle your information, contact us at {{support_email|through the contact page on our website}}.
MD;
    }

    private function terms(): string
    {
        return <<<'MD'
*Last updated: {{updated_at}}*

These Terms & Conditions ("Terms") govern your use of {{site_name}}, operated by {{company_name}} ("we", "us", "our"). Please read them carefully. By creating an account, adding funds or placing an order you agree to these Terms. If you do not agree, do not use the service.

## 1. Eligibility

You must be at least 18 years old and able to enter into a binding contract to use {{site_name}}. By registering you confirm that you meet these requirements.

## 2. Your account

You are responsible for the information you give us, for keeping your password confidential, and for everything that happens under your account. Tell us immediately if you suspect unauthorised use. One person may not hold multiple accounts to abuse offers or evade a suspension. We will never ask you for your social media passwords.

## 3. The service

{{site_name}} sells social media growth and marketing services such as followers, likes and views. We are an independent business and are **not affiliated with, endorsed by or sponsored by Instagram, Facebook, TikTok, YouTube, X (Twitter) or any other platform**. Orders are fulfilled by us or by third-party suppliers.

## 4. Platform rules and risks

Third-party platforms set their own rules, and those rules may restrict or prohibit purchased engagement. By using the service you accept that:

- we cannot guarantee how any platform will treat your account or content, and we are not responsible for any action a platform takes, including warnings, restrictions, removal of content or suspension of accounts;
- results depend on factors outside our control, and delivery times shown are estimates, not guarantees;
- some engagement may drop over time. Where a service is described as having a refill guarantee, that guarantee applies only as described for that service; and
- you use the service at your own risk and are responsible for ensuring your use complies with the rules of the platform concerned.

## 5. Placing orders

To place an order you choose a service, provide a **public** profile or post link, and enter a quantity within the limits shown. The total price is displayed before you confirm. You are responsible for entering the correct link: orders for wrong, private, deleted or unavailable links cannot be refunded once processing has started. Do not change a username, make an account private, or place multiple orders for the same link at the same time until the earlier order has finished. Once an order is being processed it generally cannot be changed or cancelled; if you make a mistake, contact us immediately and we will do our best to help.

## 6. Wallet and payments

{{site_name}} uses a prepaid wallet. You add funds through our payment provider, Paystack, and each order is paid from your wallet balance. Prices are shown in the currency displayed on the site and may change at any time; a price change does not affect an order already placed. Wallet balances do not earn interest, are not a bank account or deposit, and can only be used to pay for services on {{site_name}}. Where we need to, we may delay or refuse a payment or order to prevent fraud or comply with the law.

## 7. Refunds

- If an order is cancelled before delivery, or we cannot deliver it, the amount is returned **to your wallet**.
- If an order is only partly delivered, the charge for the undelivered portion is returned to your wallet.
- Orders that have been delivered as described are not refundable.
- Funds returned to your wallet can be used on future orders. Wallet balances are not exchangeable for cash except where the law requires it or we agree otherwise in writing.

## 8. Acceptable use

You agree not to use {{site_name}} to promote or support anything unlawful, fraudulent, hateful, abusive, sexually exploitative or harmful; to infringe anyone's rights; to attempt to disrupt or gain unauthorised access to the site, other accounts or our systems; to use automated tools to scrape or overload the site; or to dispute a legitimate payment dishonestly (including chargeback abuse).

## 9. Suspension and termination

We may suspend or close an account, cancel orders and withhold services if we reasonably believe you have broken these Terms or the law, or to protect the security of the service. You may stop using the service and ask us to close your account at any time. Closing an account does not cancel orders already in progress.

## 10. Disclaimers

The service is provided "as is" and "as available". To the fullest extent permitted by law, we do not promise that the service will be uninterrupted or error-free, or that it will achieve any particular result for you.

## 11. Limitation of liability

To the fullest extent permitted by law, we are not liable for indirect, incidental or consequential losses, or for loss of profit, revenue, data, followers, reputation or opportunity. Our total liability for any claim relating to an order is limited to the amount you paid for that order. Nothing in these Terms excludes liability that cannot be excluded by law.

## 12. Privacy

How we handle personal information is described in our [Privacy Policy](/privacy-policy), which forms part of these Terms.

## 13. Changes to these Terms

We may update these Terms from time to time. The "Last updated" date shows when they last changed. Continuing to use the service after a change means you accept the updated Terms.

## 14. Governing law

These Terms are governed by the laws of {{country|the jurisdiction in which we are established}}, and any dispute will be handled by the courts of that jurisdiction, unless the law gives you the right to bring a claim elsewhere.

## 15. Contact

Questions about these Terms? Contact us at {{support_email|through the contact page on our website}}.
MD;
    }
}
