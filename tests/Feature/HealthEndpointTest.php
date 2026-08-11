<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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

    public function test_failure_details_are_not_exposed_publicly(): void
    {
        config(['app.debug' => false]);
        Log::spy();

        $dsn = 'SQLSTATE[08006] could not connect to server: db.internal.example.com:5432 user=eduvidra';

        DB::shouldReceive('select')->once()->andThrow(new RuntimeException($dsn));
        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('get')->once()->andReturn('ok');
        Cache::shouldReceive('forget')->once();

        $response = $this->get('/health')->assertStatus(503);

        $response->assertJson(['ok' => false, 'checks' => ['database' => ['ok' => false]]]);
        $response->assertJsonMissingPath('checks.database.error');
        $this->assertStringNotContainsString('db.internal.example.com', $response->getContent());

        Log::shouldHaveReceived('error')->once();
    }

    public function test_failure_details_are_exposed_when_debugging(): void
    {
        config(['app.debug' => true]);

        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('connection refused'));
        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('get')->once()->andReturn('ok');
        Cache::shouldReceive('forget')->once();

        $this->get('/health')
            ->assertStatus(503)
            ->assertJson(['checks' => ['database' => ['error' => 'connection refused']]]);
    }
}
