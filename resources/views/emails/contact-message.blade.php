<x-mail.layout
    :title="$contact->subject"
    :preheader="'New contact message from '.$contact->name"
    eyebrow="New contact message"
    cta-text="Open admin inbox"
    :cta-url="url('/admin/messages')">
    @include('emails.partials.rows', ['rows' => [
        ['From', $contact->name],
        ['Email', $contact->email],
        ['Received', $contact->created_at->format('M j, Y · H:i')],
    ]])
    <div class="bg-soft" style="margin:14px 0 0;padding:16px 18px;background-color:#faf7f0;border:1px solid #ece5d3;border-radius:12px;white-space:pre-wrap;font-size:14px;line-height:1.65;">{{ $contact->message }}</div>
    <p class="t-muted" style="margin:14px 0 0;font-size:13px;color:#7a7888;">Hit reply to answer {{ explode(' ', $contact->name)[0] }} directly. Your reply goes to {{ $contact->email }}.</p>
</x-mail.layout>
