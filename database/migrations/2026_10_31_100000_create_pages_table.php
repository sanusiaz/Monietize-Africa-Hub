<?php

use Database\Seeders\DefaultPages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Privacy Policy and Terms & Conditions ship with the site; every word is editable in Admin → Pages.
        (new DefaultPages)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
