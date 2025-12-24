<?php

namespace App\Console\Commands;

use Database\Seeders\UpdateListingItemSalesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunDailyUpdateListingSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-daily-update-listing-sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run UpdateListingItemSalesSeeder daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        (new UpdateListingItemSalesSeeder())->run();

        $this->info('UpdateListingItemSalesSeeder executed.');
    }
}
