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
        Schema::create('messaging_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('protocol');
            $table->string('email');
            $table->string('user_id');
            $table->string('password');
            $table->string('server');
            $table->integer('port');
            $table->boolean('requires_ssl')->default(0);
            $table->boolean('requires_tls')->default(0);
            $table->boolean('requires_authentication')->default(0);
            $table->integer('ssl_port');
            $table->integer('tls_port');
            $table->timestamp('filter_from_date');
            $table->text('folders');
            $table->timestamp('last_sync_date');
            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messaging_account');
    }
};
