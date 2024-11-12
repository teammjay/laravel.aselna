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
        Schema::table('store', function (Blueprint $table) {


            $table->boolean('alter_image_files')->nullable()->default(0)->after('image_base_path');
            $table->string('resize_images')->nullable()->after('alter_image_files');
            $table->boolean('apply_watermark')->nullable()->default(0)->after('resize_images');
            $table->string('watermark_image_id')->nullable()->after('apply_watermark');
            $table->string('watermark_transparency')->nullable()->after('watermark_image_id');
            $table->string('images_location')->nullable()->after('watermark_transparency');
            $table->string('app_id')->nullable()->after('service_url');
            $table->string('dev_id')->nullable()->after('app_id');
            $table->text('certificate')->nullable()->after('dev_id');
            $table->boolean('authorized')->nullable()->default(0)->after('certificate');
            $table->string('seller_id')->nullable()->after('authorized');
            $table->string('first_name')->nullable()->after('seller_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('company')->nullable()->after('last_name');
            $table->string('address_1')->nullable()->after('company');
            $table->string('address_2')->nullable()->after('address_1');
            $table->string('address_3')->nullable()->after('address_2');
            $table->string('city')->nullable()->after('address_3');
            $table->string('country')->nullable()->after('city');
            $table->string('district')->nullable()->after('country');
            $table->string('state')->nullable()->after('district');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country_code')->nullable()->after('postal_code');
            $table->string('phone')->nullable()->after('country_code');
            $table->string('email')->nullable()->after('phone');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store', function (Blueprint $table) {
            //
        });
    }
};
