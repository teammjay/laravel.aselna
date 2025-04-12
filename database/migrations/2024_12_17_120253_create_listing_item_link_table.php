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
        Schema::create('listing_item_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id')->nullable();
            $table->uuid('listing_id')->nullable();
            $table->uuid('feed_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('client')->nullable();
            $table->uuid('replaced_id')->nullable();
            $table->uuid('order_operation_item_id')->nullable();
            $table->float('old_price')->nullable();
            $table->integer('sync_status')->nullable();
            $table->string('feed_message_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_item_link');
    }
};
