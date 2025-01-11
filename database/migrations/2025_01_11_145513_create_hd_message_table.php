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
        Schema::create('hd_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->uuid('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('hd_ticket')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->timestamp('message_date');
            $table->string('to_')->nullable();
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->string('message_id')->nullable();
            $table->uuid('messaging_account_id');
            $table->foreign('messaging_account_id')->references('id')->on('messaging_account')->onDelete('cascade');
            $table->string('message_hash_id')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->text('message')->nullable();
            $table->text('previous_message')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->boolean('draft');
            $table->string('to_name')->nullable();
            $table->text('links')->nullable();
            $table->string('from_message_user_id')->nullable();
            $table->string('to_message_user_id')->nullable();
            $table->integer('author')->nullable();
            $table->integer('order_operation_action')->nullable();
            $table->boolean('parsed_message')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->string('reply_to_email')->nullable();
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
        Schema::dropIfExists('hd_message');
    }
};
