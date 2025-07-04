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
        Schema::table('manage_marketplace_products', function (Blueprint $table) {
            $table->string('parent_id')->after('marketplace_variant_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')
                ->on('manage_marketplace_products')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->enum('upload_status', ['pending', 'in_progress', 'success', 'failed', 'cancelled'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_marketplace_products', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->enum('upload_status', ['pending', 'success', 'failed'])->default('pending')->change();
        });
    }
};
