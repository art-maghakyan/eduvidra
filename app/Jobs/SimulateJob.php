<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SimulateJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly bool $shouldFail = false) {}

    public function tags(): array
    {
        return ['simulate', $this->shouldFail ? 'simulate:fail' : 'simulate:ok'];
    }

    public function handle(): void
    {
        sleep(2);

        if ($this->shouldFail) {
            throw new Exception('Simulated job failure.');
        }
    }
}
