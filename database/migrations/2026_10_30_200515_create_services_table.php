<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_service_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('rate', 12, 2)->comment('price per 1000');
            $table->unsignedInteger('min')->default(10);
            $table->unsignedInteger('max')->default(10000);
            $table->boolean('drip_feed')->default(false);
            $table->boolean('refill')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
