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
        Schema::table('listing_requirements', function (Blueprint $table) {
            $table->boolean('is_common')->default(false)->after('value_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_requirements', function (Blueprint $table) {
            $table->dropColumn('is_common');
        });
    }
};
