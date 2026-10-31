<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with(['category:id,name,platform', 'provider:id,name'])
            ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->latest()->paginate(12)->withQueryString();

        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'platform']),
            'providers' => Provider::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('category', 'search'),
        ]);
    }

    public function store(Request $request)
    {
        Service::create($this->data($request));

        return back()->with('success', 'Service created.');
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->data($request));

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        if ($service->orders()->exists()) {
            $service->update(['is_active' => false]);

            return back()->with('success', 'Service has orders, so it was deactivated instead of deleted.');
        }

        $service->delete();

        return back()->with('success', 'Service deleted.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'category_id' => 'required|exists:categories,id',
            'provider_id' => 'nullable|exists:providers,id',
            'provider_service_id' => 'nullable|string|max:60',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'rate' => 'required|numeric|decimal:0,2|min:0|max:100000000',
            'min' => 'required|integer|min:1|max:1000000000',
            'max' => 'required|integer|gte:min|max:1000000000',
            'drip_feed' => 'required|boolean',
            'refill' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);
    }
}
