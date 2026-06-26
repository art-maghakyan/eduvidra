<?php

namespace App\Console\Commands;

use App\Jobs\SimulateJob;
use Illuminate\Console\Command;

class SimulateQueue extends Command
{
    protected $signature = 'queue:simulate
                            {--queue=default : Queue to dispatch to (default, emails, reports)}
                            {--fail : Make the job throw an exception}
                            {--count=1 : Number of jobs to dispatch}';

    protected $description = 'Dispatch simulate jobs to a queue for Horizon testing';

    public function handle(): void
    {
        $queue = $this->option('queue');
        $shouldFail = (bool) $this->option('fail');
        $count = (int) $this->option('count');

        $allowed = ['default', 'emails', 'reports'];

        if (! in_array($queue, $allowed)) {
            $this->error("Invalid queue \"{$queue}\". Allowed: ".implode(', ', $allowed));

            return;
        }

        for ($i = 0; $i < $count; $i++) {
            SimulateJob::dispatch($shouldFail)->onQueue($queue);
        }

        $status = $shouldFail ? 'failing' : 'successful';
        $this->info("Dispatched {$count} {$status} job(s) to the [{$queue}] queue.");
    }
}
