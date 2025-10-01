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
        Schema::create('fba_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('asin')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('units_ordered_l30')->default(0);
            $table->integer('sessions_l30')->default(0);
            $table->integer('units_ordered_l60')->default(0);
            $table->integer('sessions_l60')->default(0);
            $table->string('original_fba_sku')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_inventory');
    }
};
