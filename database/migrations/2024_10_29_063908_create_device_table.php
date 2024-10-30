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
            $table->id();
            // Add all this as table colums
            // dimensions_height, dimensions_width,model_name,release_date, screen_size_unit,image, thumbnail_image
            // notes, order, code, dimensions_length, dimensions_length_unit.
            $table->string('dimensions_height', 50)->nullable();
            $table->string('dimensions_width', 50)->nullable();
            $table->string('name', 50)->nullable();
            $table->date('release_date')->nullable();
            $table->string('image', 50)->nullable();
            $table->string('thumbnail_image', 50)->nullable();
            $table->string('notes', 50)->nullable();
            $table->string('order', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->string('dimensions_length', 50)->nullable();
            $table->string('dimensions_length_unit', 50)->nullable();

            // and this as foreign keys
            //device_sub_type_id, device_series_id, rating_id, screen_id,brand_id, device_type_id
            $table->foreignId('device_sub_type_id')->nullable()->constrained('device_sub_types');
            $table->foreignId('device_series_id')->nullable()->constrained('device_series');
            $table->foreignId('rating_id')->nullable()->constrained('device_ratings');
            $table->foreignId('screen_id')->nullable()->constrained('device_screens');
            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->foreignId('device_type_id')->nullable()->constrained('device_types');


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
