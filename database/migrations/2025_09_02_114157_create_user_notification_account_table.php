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
        Schema::create('user_notification_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('protocol');
            $table->string('email');
            $table->string('user_id');
            $table->string('username');
            $table->string('password');
            $table->string('server');
            $table->integer('port');
            $table->boolean('requires_ssl')->default(false);
            $table->boolean('requires_tls')->default( false);
            $table->boolean('requires_authentication')->default(false);
            $table->integer('ssl_port')->nullable();
            $table->integer('tls_port')->nullable();
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
        Schema::dropIfExists('user_notification_account');
    }
};
