<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\Notifier;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show(Seo $seo)
    {
        $seo->page(Seo::value('seo_contact_title'), Seo::value('seo_contact_description'), jsonLd: [
            ['@type' => 'ContactPage', 'name' => Seo::value('seo_contact_title'), 'url' => url('/contact'), 'isPartOf' => ['@id' => url('/').'#website']],
            $seo->organization(),
        ]);

        return Inertia::render('Contact');
    }

    public function store(Request $request, Notifier $notifier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:190',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|min:10|max:5000',
            'website' => 'nullable|string|max:0', // honeypot: real visitors never see or fill this
        ]);

        unset($data['website']);

        $message = ContactMessage::create($data + ['ip_address' => $request->ip()]);
        $notifier->contactReceived($message);

        return back()->with('success', 'Thank you — your message has been sent. We will get back to you by email.');
    }
}
