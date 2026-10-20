<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for sorting the UPC codes list page. Without them every page of the DataTable
     * sorted the whole upc_codes table (seconds per page on ~700k rows).
     * Skips an index that already exists on the same columns.
     */
    public function up(): void
    {
        if (!Schema::hasIndex('upc_codes', ['company_prefix', 'product_number'])) {
            Schema::table('upc_codes', function (Blueprint $table) {
                $table->index(['company_prefix', 'product_number'], 'idx_upc_codes_prefix_product');
            });
        }

        if (!Schema::hasIndex('upc_codes', ['product_number'])) {
            Schema::table('upc_codes', function (Blueprint $table) {
                $table->index('product_number', 'idx_upc_codes_product_number');
            });
        }

        if (!Schema::hasIndex('upc_codes', ['sequence_id'])) {
            Schema::table('upc_codes', function (Blueprint $table) {
                $table->index('sequence_id', 'idx_upc_codes_sequence_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['idx_upc_codes_prefix_product', 'idx_upc_codes_product_number', 'idx_upc_codes_sequence_id'] as $index) {
            if (Schema::hasIndex('upc_codes', $index)) {
                Schema::table('upc_codes', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }
    }
};
