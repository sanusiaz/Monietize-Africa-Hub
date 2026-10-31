<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PageController extends Controller
{
    /** Pages other parts of the site (footer, emails, sign-up) rely on. */
    private const PROTECTED = ['privacy-policy', 'terms-and-conditions'];

    public function index()
    {
        return Inertia::render('Admin/Pages/Index', [
            'pages' => Page::orderBy('sort_order')->orderBy('title')->get(),
            'protected' => self::PROTECTED,
            'reserved' => Page::RESERVED,
        ]);
    }

    public function store(Request $request)
    {
        Page::create($this->data($request));

        return back()->with('success', 'Page created.');
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->data($request, $page);

        if (in_array($page->slug, self::PROTECTED, true)) {
            $data['slug'] = $page->slug; // links to these pages exist elsewhere
        }

        $page->update($data);

        return back()->with('success', 'Page saved.');
    }

    public function destroy(Page $page)
    {
        if (in_array($page->slug, self::PROTECTED, true)) {
            return back()->with('error', 'This page is linked from other parts of the site. Edit it, or unpublish it instead.');
        }

        $page->delete();

        return back()->with('success', 'Page deleted.');
    }

    /** Render markdown exactly as visitors will see it. */
    public function preview(Request $request)
    {
        $request->validate(['content' => 'nullable|string|max:200000']);

        return response()->json(['html' => Page::toHtml((string) $request->input('content', ''))]);
    }

    private function data(Request $request, ?Page $page = null): array
    {
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title')))]);

        return $request->validate([
            'title' => 'required|string|max:150',
            'slug' => ['required', 'string', 'max:120', 'unique:pages,slug'.($page ? ",{$page->id}" : ''),
                fn ($attr, $value, $fail) => in_array($value, Page::RESERVED, true) ? $fail('That address is used by the application. Choose another.') : null],
            'content' => 'required|string|max:200000',
            'meta_description' => 'nullable|string|max:300',
            'is_published' => 'required|boolean',
            'show_in_footer' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];
    }
}
