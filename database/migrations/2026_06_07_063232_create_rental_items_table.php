<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('rental_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('rental_package_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('item_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
