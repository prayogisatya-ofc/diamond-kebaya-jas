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
        Schema::create('rental_package_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('rental_package_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('default_item_price', 12, 2)->nullable();
            $table->boolean('is_optional')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rental_package_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_package_items');
    }
};
