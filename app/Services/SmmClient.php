<?php

namespace App\Services;

use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Client for the de-facto standard SMM panel API (v2): key + action over POST. */
class SmmClient
{
    public function __construct(private Provider $provider) {}

    private function call(array $params): array
    {
        $response = Http::asForm()->timeout(25)->withoutRedirecting()->acceptJson()
            ->post($this->provider->api_url, ['key' => $this->provider->api_key] + $params);

        if (! $response->successful()) {
            throw new RuntimeException("Supplier returned HTTP {$response->status()}.");
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Supplier returned an unreadable response.');
        }

        if (isset($json['error'])) {
            throw new RuntimeException((string) $json['error']);
        }

        return $json;
    }

    /** @return array{balance: float, currency: string|null} */
    public function balance(): array
    {
        $r = $this->call(['action' => 'balance']);

        return ['balance' => (float) ($r['balance'] ?? 0), 'currency' => $r['currency'] ?? null];
    }

    public function services(): array
    {
        return $this->call(['action' => 'services']);
    }

    public function add(string $serviceId, string $link, int $quantity): string
    {
        $r = $this->call(['action' => 'add', 'service' => $serviceId, 'link' => $link, 'quantity' => $quantity]);

        if (empty($r['order'])) {
            throw new RuntimeException('Supplier did not return an order ID.');
        }

        return (string) $r['order'];
    }

    public function status(string $orderId): array
    {
        return $this->call(['action' => 'status', 'order' => $orderId]);
    }
}
