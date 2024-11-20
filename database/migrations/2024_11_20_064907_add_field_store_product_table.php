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
        Schema::table('store_product', function (Blueprint $table) {
            $table->uuid('listing_generation_type_id')->nullable()->after('manufacturer_id');
            $table->uuid('listing_title_template_id')->nullable()->after('listing_generation_type_id');
            $table->boolean('preserve_title_description')->default(0)->after('listing_title_template_id');
            $table->float('fulfillment_by_channel_price')->nullable()->after('preserve_title_description');
            $table->timestamp('last_update_date_time')->nullable()->after('fulfillment_by_channel_price');
            $table->timestamp('last_synch_date_time')->nullable()->after('last_update_date_time');
            $table->boolean('overwrite_price')->default(0)->after('last_synch_date_time');
            $table->boolean('overwrite_msrp')->default(0)->after('last_synch_date_time');
            $table->boolean('overwrite_fulfillment_by_channel_price')->default(0)->after('last_synch_date_time');
            $table->boolean('overwrite_title')->default(0)->after('last_synch_date_time');
            $table->boolean('overwrite_description')->default(0)->after('last_synch_date_time');
            $table->boolean('overwrite_wholesale_price')->default(0)->after('last_synch_date_time');
            $table->boolean('overwrite_compare_at_price')->default(0)->after('last_synch_date_time');
            $table->float('wholesale_price')->nullable()->after('overwrite_compare_at_price');
            $table->float('compare_at_price')->nullable()->after('wholesale_price');
            $table->string('long_tail_keywords')->nullable()->after('compare_at_price');
            $table->string('short_tail_keywords')->nullable()->after('long_tail_keywords');
            $table->integer('fulfillment_type')->nullable()->after('short_tail_keywords');
            $table->boolean('synch_price')->nullable()->default(0)->after('fulfillment_type');
            $table->boolean('synch_inventory')->nullable()->default(0)->after('synch_price');
            $table->boolean('synch_item')->nullable()->default(0)->after('synch_inventory');
            $table->string('errors')->nullable()->change();
            $table->float('profit_rate')->nullable()->change();
            $table->uuid('channel_category_id')->nullable()->change();
            $table->integer('sales_count')->nullable()->change();
            $table->date('last_sale_date')->nullable()->change();
            $table->boolean('include_carriers')->nullable()->change();
            $table->boolean('brand_combo')->nullable()->change();
            $table->boolean('carrier_combo')->nullable()->change();
            $table->float('fulfillment_by_channel_price')->nullable()->change();
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
