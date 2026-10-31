<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Live chat is an optional feature that sets its own cookies, so the shipped Privacy Policy says so.
     * Only touches the page if that section is still exactly as it was shipped; an edited policy is left alone.
     */
    public function up(): void
    {
        $old = <<<'MD'
- a **security (CSRF) cookie** that protects forms from forgery; and
- your **theme preference** (light or dark), saved in your browser's local storage.
MD;
        $new = <<<'MD'
- a **security (CSRF) cookie** that protects forms from forgery;
- your **theme preference** (light or dark), saved in your browser's local storage; and
- if **live chat** is switched on, cookies or local storage set by our chat provider so your conversation can continue.
MD;

        $page = Page::where('slug', 'privacy-policy')->first();

        if ($page && str_contains($page->content, $old)) {
            $page->update(['content' => str_replace($old, $new, $page->content)]);
        }
    }

    public function down(): void
    {
        //
    }
};
