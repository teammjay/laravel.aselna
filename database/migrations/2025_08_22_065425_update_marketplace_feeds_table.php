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
        Schema::table('marketplace_feeds', function (Blueprint $table) {
            $table->string('file_name')->nullable()->change();
            $table->string('path')->nullable()->change();
            $table->boolean('status')->default(false)->after('path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_feeds', function (Blueprint $table) {
            $table->string('file_name')->change();
            $table->string('path')->change();
            $table->dropColumn('status');
        });
    }
};
