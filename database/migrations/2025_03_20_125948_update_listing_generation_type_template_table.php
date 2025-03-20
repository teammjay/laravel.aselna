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
        Schema::table('listing_generation_type_template', function (Blueprint $table) {
            $table->string('used_by_store')->nullable()->after('client');
            $table->boolean('status')->default(1)->nullable()->after('used_by_store');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
