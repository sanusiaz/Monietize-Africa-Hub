<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function credit(User $user, float $amount, string $source, ?string $reference = null, ?string $description = null): WalletTransaction
    {
        return $this->apply($user, 'credit', $amount, $source, $reference, $description);
    }

    public function debit(User $user, float $amount, string $source, ?string $reference = null, ?string $description = null): WalletTransaction
    {
        return $this->apply($user, 'debit', $amount, $source, $reference, $description);
    }

    private function apply(User $user, string $type, float $amount, string $source, ?string $reference, ?string $description): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $type, $amount, $source, $reference, $description) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $new = $type === 'credit' ? $locked->balance + $amount : $locked->balance - $amount;

            if ($new < 0) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $locked->update(['balance' => $new]);
            $user->balance = $new;

            return $locked->walletTransactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $new,
                'source' => $source,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }
}
