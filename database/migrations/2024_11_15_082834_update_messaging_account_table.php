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
        Schema::table('messaging_account', function (Blueprint $table) {
            $table->integer('ssl_port')->nullable()->change();
            $table->integer('tls_port')->nullable()->change();
            $table->timestamp('filter_from_date')->nullable()->change();
            $table->text('folders')->nullable()->change();
            $table->timestamp('last_sync_date')->nullable()->change();
            $table->string('created_by')->nullable()->change();
            $table->string('updated_by')->nullable()->change();
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
