<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([]);

        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('get')->once()->andReturn('ok');
        Cache::shouldReceive('forget')->once();

        $this->get('/health')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson([
                'ok' => true,
                'checks' => [
                    'database' => ['ok' => true],
                    'cache' => ['ok' => true],
                ],
            ]);
    }
}

