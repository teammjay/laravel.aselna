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
            if (!Schema::hasColumn('product_specification_info', 'field_type')) {
                $table->string('field_type')->nullable()->after('field_local_name');
            }
            if (!Schema::hasColumn('product_specification_info', 'field_description')) {
                $table->text('field_description')->nullable()->after('field_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_specification_info', function (Blueprint $table) {
            $table->dropColumn(['field_type', 'field_description']);
        });
    }
};
