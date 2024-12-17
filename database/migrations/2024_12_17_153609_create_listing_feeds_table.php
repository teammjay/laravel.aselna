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
        Schema::create('listing_feeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id');
            $table->uuid('listing_id');
            $table->foreign('listing_id')->references('id')->on('listing');
            $table->integer('number_of_attempts');
            $table->integer('status');
            $table->longText('error_text');
            $table->string('feed_id', 255);
            $table->timestamp('submit_ts');
            $table->longText('errors');
            $table->timestamp('last_status_check_ts');
            $table->integer('status_check_count');
            $table->integer('processing_count');
            $table->integer('success_count');
            $table->integer('fail_count');
            $table->integer('warning_count');
            $table->integer('number_of_items');
            $table->uuid('client');
            $table->string('file_reference_id', 512);
            $table->string('store_feed_type', 255);
            $table->uuid('store_id');
            $table->integer('feed_type');
            $table->uuid('response_file_id');
            $table->integer('feed_execution_delay');
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_feeds');
    }
};
