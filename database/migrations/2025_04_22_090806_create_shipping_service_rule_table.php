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
        Schema::create('shipping_service_rule', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('min_weight_value')->nullable();
            $table->integer('min_weight_weight_unit')->nullable();
            $table->float('max_weight_value')->nullable();
            $table->integer('max_weight_weight_unit')->nullable();
            $table->integer('priority');
            $table->float('min_price')->default('0.0');
            $table->float('max_price')->default('0.0');
            $table->integer('signature_confirmation')->nullable();
            $table->uuid('client_id')->nullable();
            $table->integer('version')->default(1);
            $table->integer('weight_operator')->default(0);
            $table->integer('price_operator')->default(0);
            $table->float('round_weight_value')->nullable();
            $table->integer('round_weight_weight_unit')->nullable();
            $table->uuid('package_type_filter_id')->nullable();
            $table->uuid('shipping_carrier_service_id')->nullable();
            $table->foreign('shipping_carrier_service_id')->references('id')->on('shipping_carrier_service')->onDelete('cascade');
            $table->string('color')->nullable();
            $table->integer('quantity_operator');
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->default(0);
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
        Schema::dropIfExists('shipping_service_rule');
    }
};
