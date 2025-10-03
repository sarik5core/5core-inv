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
        Schema::table('fba_inventory', function (Blueprint $table) {
            $table->integer('total_quantity')->default(0);
            $table->integer('sellable_quantity')->default(0);
            $table->integer('unsellable_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('inbound_quantity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fba_inventory', function (Blueprint $table) {
            $table->dropColumn(['total_quantity', 'sellable_quantity', 'unsellable_quantity', 'reserved_quantity', 'inbound_quantity']);
        });
    }
};
