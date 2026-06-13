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
        Schema::table('rental_whatsapp_notifications', function (Blueprint $table) {
            $table->index('rental_id', 'rental_whatsapp_notifications_rental_id_index');
            $table->dropUnique(['rental_id', 'type']);
            $table->unique(['rental_id', 'type', 'scheduled_for'], 'rental_whatsapp_notifications_unique_daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_whatsapp_notifications', function (Blueprint $table) {
            $table->dropUnique('rental_whatsapp_notifications_unique_daily');
            $table->unique(['rental_id', 'type']);
            $table->dropIndex('rental_whatsapp_notifications_rental_id_index');
        });
    }
};
