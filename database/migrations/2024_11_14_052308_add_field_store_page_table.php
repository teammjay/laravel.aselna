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
        Schema::table('store_page', function (Blueprint $table) {
            $table->dropColumn('page_type');
            $table->uuid('page_type_id');
            $table->string('created_by', 50)->nullable()->after('text');
            $table->string('updated_by', 50)->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('store_page', function (Blueprint $table) {
            $table->uuid('page_type');
        });
    }
};
