<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Rules\PublicHttpsUrl;
use App\Services\SmmClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class ProviderController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Providers/Index', [
            'providers' => Provider::withCount('services')->latest()->get(),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'platform']),
        ]);
    }

    public function store(Request $request)
    {
        Provider::create($request->validate([
            'name' => 'required|string|max:120',
            'api_url' => ['required', 'string', 'max:300', new PublicHttpsUrl],
            'api_key' => 'required|string|max:500',
            'is_active' => 'required|boolean',
        ]));

        return back()->with('success', 'Provider added.');
    }

    public function update(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'api_url' => ['required', 'string', 'max:300', new PublicHttpsUrl],
            'api_key' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $provider->update($data);

        return back()->with('success', 'Provider updated.');
    }

    public function destroy(Provider $provider)
    {
        $provider->delete();

        return back()->with('success', 'Provider removed.');
    }

    public function balance(Provider $provider)
    {
        try {
            return response()->json((new SmmClient($provider))->balance());
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** The supplier's service list, for the import picker. */
    public function catalogue(Provider $provider)
    {
        try {
            $imported = $provider->services()->pluck('provider_service_id')->all();

            return response()->json(collect((new SmmClient($provider))->services())->map(fn ($s) => [
                'id' => (string) ($s['service'] ?? ''),
                'name' => (string) ($s['name'] ?? ''),
                'category' => (string) ($s['category'] ?? ''),
                'rate' => (float) ($s['rate'] ?? 0),
                'min' => (int) ($s['min'] ?? 1),
                'max' => (int) ($s['max'] ?? 1),
                'refill' => (bool) ($s['refill'] ?? false),
                'imported' => in_array((string) ($s['service'] ?? ''), $imported, true),
            ])->filter(fn ($s) => $s['id'] !== '')->values());
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function import(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'markup' => 'required|numeric|min:0|max:1000',
            'services' => 'required|array|min:1|max:500',
            'services.*' => 'string',
        ]);

        try {
            $catalogue = collect((new SmmClient($provider))->services())->keyBy(fn ($s) => (string) ($s['service'] ?? ''));
        } catch (Throwable $e) {
            return back()->with('error', 'Supplier error: '.$e->getMessage());
        }

        $count = 0;
        foreach ($data['services'] as $id) {
            $s = $catalogue->get($id);
            if (! $s) {
                continue;
            }

            Service::updateOrCreate(
                ['provider_id' => $provider->id, 'provider_service_id' => $id],
                [
                    'category_id' => $data['category_id'],
                    'name' => mb_substr((string) $s['name'], 0, 200),
                    'rate' => round((float) $s['rate'] * (1 + $data['markup'] / 100), 2),
                    'min' => max(1, (int) ($s['min'] ?? 1)),
                    'max' => max(1, (int) ($s['max'] ?? 1)),
                    'refill' => (bool) ($s['refill'] ?? false),
                ],
            );
            $count++;
        }

        return back()->with('success', "Imported {$count} service(s) with a {$data['markup']}% markup.");
    }
}
