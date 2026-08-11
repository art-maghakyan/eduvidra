<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => function (): void {
                DB::select('select 1');
            },
            'cache' => function (): void {
                $key = 'health-http:'.Str::uuid();

                Cache::put($key, 'ok', 5);

                if (Cache::get($key) !== 'ok') {
                    throw new RuntimeException('Cache read check failed.');
                }

                Cache::forget($key);
            },
        ];

        $results = [];
        $failed = false;

        foreach ($checks as $name => $check) {
            try {
                $check();
                $results[$name] = ['ok' => true];
            } catch (Throwable $exception) {
                $failed = true;

                // The endpoint is public and unauthenticated, and driver
                // exceptions routinely embed the database host, port, name and
                // user. Log the detail; return it only when debugging.
                Log::error("Health check failed: {$name}", ['exception' => $exception]);

                $results[$name] = ['ok' => false] + (config('app.debug')
                    ? ['error' => $exception->getMessage()]
                    : []);
            }
        }

        $status = $failed ? 503 : 200;

        return response()
            ->json([
                'ok' => ! $failed,
                'checks' => $results,
            ], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
