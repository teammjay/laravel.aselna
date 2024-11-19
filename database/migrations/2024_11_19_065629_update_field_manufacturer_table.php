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
        Schema::table('manufacturer', function (Blueprint $table) {
            $table->dropColumn('image_id');
            $table->string('image_url')->nullable();
            $table->dropColumn('client');
            $table->uuid('client_id')->after('thumbnail_id');
            $table->string('created_by')->nullable()->change();
            $table->string('updated_by')->nullable()->change();
            $table->string('deleted_by')->nullable()->change();
            $table->timestamp('deleted_at')->nullable()->change();
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
