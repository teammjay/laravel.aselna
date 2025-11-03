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
        Schema::table('product_specification_info', function (Blueprint $table) {
            // Add column name markeplace_id. as nullable string
            $table->string('marketplace_id')->nullable()->after('field_local_name');
        });

        Schema::table('product_specification_values', function (Blueprint $table) {
            // Add column name markeplace_value_id. as nullable string
            $table->string('marketplace_value_id')->nullable()->after('field_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_specifications', function (Blueprint $table) {
            $table->dropColumn('marketplace_id');
        });

        Schema::table('product_specification_values', function (Blueprint $table) {
            $table->dropColumn('marketplace_value_id');
        });
    }
};
