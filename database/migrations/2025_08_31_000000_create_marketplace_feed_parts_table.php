<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceFeedPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_feed_parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feed_id');
            $table->integer('part_number');
            $table->string('file_name');
            $table->string('path');
            $table->timestamps();

            $table->foreign('feed_id')
                  ->references('id')
                  ->on('marketplace_feeds')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketplace_feed_parts');
    }
}
