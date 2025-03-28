<?php

namespace App\Console\Commands;

use App\Jobs\FetchResultFeedAsync;
use Illuminate\Console\Command;

class ResultFeedSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:result-feed-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        FetchResultFeedAsync::dispatch();
    }
}
