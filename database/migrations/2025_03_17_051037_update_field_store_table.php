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
        Schema::table('store', function (Blueprint $table) {
            $table->longText('api_key',2048)->nullable()->change();
            $table->longText('access_key',2048)->nullable()->change();
            $table->longText('secret_key',2048)->nullable()->change();
            $table->string('service_url',1000)->nullable()->change();
            $table->string('image_base_path',500)->nullable()->change();
            $table->string('certificate',1024)->nullable()->change();
            $table->string('fulfilment_node_id',512)->nullable()->change();
            $table->string('store_url',1024)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store', function (Blueprint $table) {
            $table->string('api_key', 255)->nullable()->change();
            $table->string('access_key', 255)->nullable()->change();
            $table->string('secret_key', 255)->nullable()->change();
            $table->string('service_url', 255)->nullable()->change();
            $table->string('image_base_path', 255)->nullable()->change();
            $table->string('certificate', 255)->nullable()->change();
            $table->string('fulfilment_node_id', 255)->nullable()->change();
            $table->string('store_url', 255)->nullable()->change();
        });
    }
};
