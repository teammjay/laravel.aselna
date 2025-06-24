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
        Schema::table('printer', function (Blueprint $table) {
            $table->dropForeign(['printer_client_id']);

            $table->integer('status')->nullable()->change();
            $table->integer('dpi')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('printer', function (Blueprint $table) {
            $table->foreign('printer_client_id')->references('id')->on('printer_client')->onDelete('cascade');
            $table->integer('status')->nullable(false)->change();
            $table->integer('dpi')->nullable(false)->change();
        });
    }
};
