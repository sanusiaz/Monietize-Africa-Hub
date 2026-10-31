<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('services')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($this->data($request));

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->data($request, $category));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->services()->exists()) {
            return back()->with('error', 'Move or delete the services in this category first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function data(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'platform' => 'required|string|max:40|alpha_dash:ascii',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);
        $data['sort_order'] ??= 0;
        $data['slug'] = $category?->slug ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4));

        return $data;
    }
}
