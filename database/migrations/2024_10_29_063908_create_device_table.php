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
        Schema::create('device', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Add all this as table colums
            // dimensions_height, dimensions_width,model_name,release_date, screen_size_unit,image, thumbnail_image
            // notes, order, code, dimensions_length, dimensions_length_unit.
            $table->string('dimensions_height', 50)->nullable();
            $table->string('dimensions_width', 50)->nullable();
            $table->string('name', 50)->nullable();
            $table->date('release_date')->nullable();
            $table->string('image', 50)->nullable();
            $table->string('thumbnail_image', 50)->nullable();
            $table->text('notes', 50)->nullable();
            $table->string('order', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->string('dimensions_length', 50)->nullable();
            $table->string('dimensions_length_unit', 50)->nullable();

            $table->uuid('device_sub_type_id')->nullable();
            $table->foreign('device_sub_type_id')->references('id')->on('device_sub_types')
                ->onDelete('cascade');

            //device_series_id
            $table->uuid('device_series_id')->nullable();
            $table->foreign('device_series_id')->references('id')->on('device_series')
                ->onDelete('cascade');

            //rating_id
            $table->uuid('rating_id')->nullable();
            $table->foreign('rating_id')->references('id')->on('device_ratings')
                ->onDelete('cascade');

            //screen_id
            $table->uuid('screen_id')->nullable();
            $table->foreign('screen_id')->references('id')->on('device_screens')
                ->onDelete('cascade');

            //brand_id
            $table->uuid('brand_id')->nullable();
            $table->foreign('brand_id')->references('id')->on('brands')
                ->onDelete('cascade');

            //device_type_id
            $table->uuid('device_type_id')->nullable();
            $table->foreign('device_type_id')->references('id')->on('device_types')
                ->onDelete('cascade');


            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device');
    }
};
