<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_systems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('type_');
            $table->string('host', 255)->nullable();
            $table->integer('port')->nullable();
            $table->string('login_id', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('root_path', 255)->nullable();
            $table->string('url_base_path', 1000)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_systems');
    }
};
