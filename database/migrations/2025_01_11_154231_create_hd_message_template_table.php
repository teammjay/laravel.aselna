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
        Schema::create('hd_message_template', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->string('name');
            $table->text('template_code');
            $table->integer('order_operation_type')->nullable();
            $table->uuid('store_type_id')->nullable();
            $table->foreign('store_type_id')->references('id')->on('store_type')->onDelete('cascade');
            $table->boolean('print_to_file')->nullable();
            $table->boolean('replacement_order_shipped')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_message_template');
    }
};
