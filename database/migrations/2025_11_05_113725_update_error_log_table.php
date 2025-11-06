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
        Schema::table('error_logs', function (Blueprint $table) {
            $table->string('hash_string', 1024)->nullable()->after('resolved_date');
            $table->integer('error_count')->default(0)->after('hash_string');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropColumn('hash_string');
            $table->dropColumn('error_count');
        });
    }
};
