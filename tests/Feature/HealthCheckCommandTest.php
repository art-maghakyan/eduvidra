<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckCommandTest extends TestCase
{
    public function test_health_check_command_passes(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([]);

        $this->artisan('health:check')
            ->expectsOutput('Checking database... OK')
            ->expectsOutput('Checking cache... OK')
            ->expectsOutput('Application health check passed.')
            ->assertExitCode(0);
    }
}
