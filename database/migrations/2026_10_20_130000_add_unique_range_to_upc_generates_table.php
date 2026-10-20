<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A company prefix + start product number + number of codes can be generated once.
     * UPCSyncJob used to insert the old DB's rows again on every run, so the duplicates are removed
     * first, keeping the earliest row of each group. upc_generates only records what was generated
     * (the codes are in upc_codes), so no codes are lost.
     */
    public function up(): void
    {
        $rows = DB::table('upc_generates')
            ->orderByRaw('created_at IS NULL')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $duplicates = $rows
            ->groupBy(fn ($row) => $row->company_prefix . '|' . $row->product_number . '|' . $row->number_of_codes)
            ->flatMap(fn ($group) => $group->slice(1));

        if ($duplicates->isNotEmpty()) {
            Log::info('Removing duplicate upc_generates rows before adding the unique index', [
                'rows' => $duplicates->values()->all(),
            ]);

            foreach ($duplicates->pluck('id')->chunk(500) as $ids) {
                DB::table('upc_generates')->whereIn('id', $ids->all())->delete();
            }
        }

        if (!Schema::hasIndex('upc_generates', ['company_prefix', 'product_number', 'number_of_codes'])) {
            Schema::table('upc_generates', function (Blueprint $table) {
                $table->unique(['company_prefix', 'product_number', 'number_of_codes'], 'upc_generates_prefix_product_codes_unique');
            });
        }
    }

    /**
     * Reverse the migrations. The removed duplicates are not restored (they are in the log).
     */
    public function down(): void
    {
        if (Schema::hasIndex('upc_generates', 'upc_generates_prefix_product_codes_unique')) {
            Schema::table('upc_generates', function (Blueprint $table) {
                $table->dropUnique('upc_generates_prefix_product_codes_unique');
            });
        }
    }
};
