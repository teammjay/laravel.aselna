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
        Schema::create('store_navigation_page', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('store_image_url', 1024)->nullable();
            $table->integer('page_type')->comment('Page type as defined in StorePageType');
            $table->string('page_id', 255)->nullable();
            $table->string('url_path', 1024)->nullable();
            $table->uuid('parent_page_id')->nullable()->constrained('store_navigation_page')->onDelete('cascade');
            $table->uuid('template_id')->nullable()->constrained('web_store_page_templates')->onDelete('cascade');
            $table->string('title', 500)->nullable();
            $table->uuid('device_brand_id')->nullable();
            $table->uuid('device_id')->nullable();
            $table->uuid('product_type_id')->nullable()->constrained('product_types')->onDelete('cascade');
            $table->longText('generated_body')->nullable();
            $table->string('keywords', 1024)->nullable();
            $table->string('meta_title', 500)->nullable();
            $table->string('meta_description', 1024)->nullable();
            $table->text('child_ids')->nullable();
            $table->string('description', 4096)->nullable();
            $table->uuid('image_id')->nullable()->constrained('sys_file')->onDelete('set null');
            $table->boolean('synchronize')->default(false);
            $table->boolean('update_image')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_navigation_page');
    }
};
