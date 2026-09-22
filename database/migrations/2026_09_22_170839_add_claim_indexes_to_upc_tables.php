<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for claiming the next unused UPC in feed generation. Without them every claim
     * read and sorted all of a store's unused codes, and upc_codes has no primary key, so
     * marking a code used by id scanned the whole table.
     * Skips an index that already exists on the same columns (some were added by hand).
     */
    public function up(): void
    {
        if (!Schema::hasIndex('upc_codes', ['store', 'is_used', 'sequence_id'])) {
            Schema::table('upc_codes', function (Blueprint $table) {
                $table->index(['store', 'is_used', 'sequence_id'], 'idx_upc_codes_store_is_used_sequence');
            });
        }

        if (!Schema::hasIndex('upc_codes', ['id'])) {
            Schema::table('upc_codes', function (Blueprint $table) {
                $table->index('id', 'idx_upc_codes_id');
            });
        }

        if (!Schema::hasIndex('good_upc', ['is_used', 'upc'])) {
            Schema::table('good_upc', function (Blueprint $table) {
                $table->index(['is_used', 'upc'], 'idx_good_upc_is_used_upc');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'upc_codes' => ['idx_upc_codes_store_is_used_sequence', 'idx_upc_codes_id'],
            'good_upc' => ['idx_good_upc_is_used_upc'],
        ] as $tableName => $indexes) {
            foreach ($indexes as $index) {
                if (Schema::hasIndex($tableName, $index)) {
                    Schema::table($tableName, function (Blueprint $table) use ($index) {
                        $table->dropIndex($index);
                    });
                }
            }
        }
    }
};
