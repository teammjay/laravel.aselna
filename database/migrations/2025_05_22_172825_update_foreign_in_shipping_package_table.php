<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_package', function (Blueprint $table) {
            $table->dropForeign(['client']);
            $table->uuid('client')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_package', function (Blueprint $table) {
//            $table->foreign('client')->references('id')->on('client')->onDelete('cascade')->change();
        });
    }
};
