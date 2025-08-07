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
        Schema::create('store_page_template', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->boolean('overwrite')->default(false);
            $table->integer('page_type')->nullable();
            $table->text('template_code');
            $table->longText('meta_description_template')->nullable();
            $table->text('meta_title_template')->nullable();
            $table->text('meta_keywords_template')->nullable();
            $table->text('title_template')->nullable();
            $table->longText('description_template')->nullable();
            $table->uuid('store_id')->nullable();
            $table->foreign('store_id')->references('id')->on('store')
                ->onDelete('cascade');
            $table->uuid('product_type_id')->nullable();
            $table->foreign('product_type_id')->references('id')->on('product_type')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_page_template');
    }
};
