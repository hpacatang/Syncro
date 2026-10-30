<?php

namespace App\Console\Commands;

use App\Services\SocialMediaPublisher;
use Illuminate\Console\Command;

class PublishScheduledSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'submissions:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically publish scheduled submissions whose date and time has arrived';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled submissions due for publication...');

        $count = SocialMediaPublisher::publishDueSubmissions();

        $this->info("Published {$count} due submission(s).");

        return self::SUCCESS;
    }
}
