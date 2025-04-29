<?php

namespace App\Console\Commands;

use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchWalmartOrdersJob;
use Illuminate\Console\Command;

class FetchOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all marketplace orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         new FetchShopifyOrders();
         new FetchWalmartOrdersJob();
    }
}
