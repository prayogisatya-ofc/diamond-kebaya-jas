<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->string('guarantee_type', 10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rentals')
            ->whereNull('guarantee_type')
            ->update(['guarantee_type' => 'ktp']);

        Schema::table('rentals', function (Blueprint $table) {
            $table->string('guarantee_type', 10)->nullable(false)->change();
        });
    }
};
