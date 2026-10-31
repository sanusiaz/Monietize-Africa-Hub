<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))
            ->withCount('orders')
            ->latest()->paginate(12)->withQueryString();

        return Inertia::render('Admin/Users/Index', ['users' => $users, 'filters' => $request->only('search')]);
    }

    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', [
            'customer' => $user,
            'transactions' => $user->walletTransactions()->latest()->limit(20)->get(),
            'orders' => $user->orders()->with('service:id,name')->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email:rfc|max:190|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,customer',
            'is_active' => 'required|boolean',
        ]);

        abort_if($user->id === $request->user()->id && (! $data['is_active'] || $data['role'] !== 'admin'), 422, 'You cannot demote or suspend yourself.');

        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    public function adjustWallet(Request $request, User $user, WalletService $wallet)
    {
        $data = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|decimal:0,2|min:1|max:100000000',
            'note' => 'nullable|string|max:200',
        ]);

        try {
            $wallet->{$data['type']}($user, (float) $data['amount'], 'admin', null, $data['note'] ?: 'Manual adjustment by admin');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Wallet updated.');
    }
}
