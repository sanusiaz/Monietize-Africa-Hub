<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('link');
            $table->unsignedInteger('quantity');
            $table->decimal('charge', 14, 2);
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('start_count')->nullable();
            $table->unsignedInteger('remains')->nullable();
            $table->string('provider_order_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
