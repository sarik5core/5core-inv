<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('ad_updates', function (Blueprint $table) {
            $table->decimal('amazon_percentage', 5, 2)->default(100)->after('some_existing_column');
        });
    }

    public function down()
    {
        Schema::table('ad_updates', function (Blueprint $table) {
            $table->dropColumn('amazon_percentage');
        });
    }
};
