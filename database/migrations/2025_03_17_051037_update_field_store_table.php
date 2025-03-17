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
            $table->text('api_key',2048)->nullable()->change();
            $table->text('access_key',2048)->nullable()->change();
            $table->text('secret_key',2048)->nullable()->change();
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
        //
    }
};
