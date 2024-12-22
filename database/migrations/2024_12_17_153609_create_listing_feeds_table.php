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
            $table->uuid('file_id')->nullable();
            $table->uuid('listing_id')->nullable();
            $table->foreign('listing_id')->references('id')->on('listing');
            $table->integer('number_of_attempts')->nullable();
            $table->integer('status')->nullable();
            $table->longText('error_text')->nullable();
            $table->string('feed_id', 255)->nullable();
            $table->timestamp('submit_ts')->nullable();
            $table->longText('errors')->nullable();
            $table->timestamp('last_status_check_ts')->nullable();
            $table->integer('status_check_count')->nullable();
            $table->integer('processing_count')->nullable();
            $table->integer('success_count')->nullable();
            $table->integer('fail_count')->nullable();
            $table->integer('warning_count')->nullable();
            $table->integer('number_of_items')->nullable();
            $table->uuid('client')->nullable();
            $table->string('file_reference_id', 512)->nullable();
            $table->string('store_feed_type', 255)->nullable();
            $table->uuid('store_id')->nullable();
            $table->integer('feed_type')->nullable();
            $table->uuid('response_file_id')->nullable();
            $table->integer('feed_execution_delay')->nullable();
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
