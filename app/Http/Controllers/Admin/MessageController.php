<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = ContactMessage::query()
            ->when($request->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")->orWhere('subject', 'like', "%$s%")))
            ->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $messages,
            'filters' => $request->only('filter', 'search'),
            'unread' => ContactMessage::whereNull('read_at')->count(),
        ]);
    }

    public function toggleRead(ContactMessage $message)
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back();
    }

    public function markAllRead()
    {
        ContactMessage::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All messages marked as read.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }
}
