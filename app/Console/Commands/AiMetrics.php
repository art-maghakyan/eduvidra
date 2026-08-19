<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Publishes a day's AI tutor usage as CloudWatch metrics.
 *
 * Emitted as a CloudWatch Embedded Metric Format log line rather than a
 * PutMetricData API call. Logs already reach CloudWatch through the awslogs
 * Docker driver in production, so EMF needs no AWS client, no credentials at
 * runtime, and no extra IAM permission — and it degrades to an ordinary log
 * line in development instead of failing. (PutMetricData is available if ever
 * wanted: the instance role carries CloudWatchAgentServerPolicy, which grants
 * it — see bin/attach-cloudwatch-policy.sh.)
 */
class AiMetrics extends Command
{
    protected $signature = 'ai:metrics {--date= : Day to report, defaults to yesterday}';

    protected $description = 'Publish AI tutor tokens, cost and latency for a day as CloudWatch metrics';

    /**
     * Haiku 4.5, USD per million tokens. Cache reads are a tenth of input and
     * 5-minute cache writes are 1.25x, which is exactly why all four are
     * counted separately rather than summed into one "input" number.
     */
    private const PRICES = [
        'input' => 1.00,
        'cache_write' => 1.25,
        'cache_read' => 0.10,
        'output' => 5.00,
    ];

    public function handle(): int
    {
        $day = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        $turns = Message::query()
            ->where('role', 'assistant')
            ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
            ->get(['input_tokens', 'output_tokens', 'cache_creation_tokens', 'cache_read_tokens', 'duration_ms']);

        $tokens = [
            'input' => (int) $turns->sum('input_tokens'),
            'cache_write' => (int) $turns->sum('cache_creation_tokens'),
            'cache_read' => (int) $turns->sum('cache_read_tokens'),
            'output' => (int) $turns->sum('output_tokens'),
        ];

        $cost = 0.0;
        foreach ($tokens as $kind => $count) {
            $cost += $count * self::PRICES[$kind] / 1_000_000;
        }

        $metrics = [
            'Turns' => $turns->count(),
            'InputTokens' => $tokens['input'],
            'CacheWriteTokens' => $tokens['cache_write'],
            'CacheReadTokens' => $tokens['cache_read'],
            'OutputTokens' => $tokens['output'],
            'CostUsd' => round($cost, 6),
            'LatencyP95Ms' => $this->percentile($turns->pluck('duration_ms')->filter()->all(), 0.95),
            'LatencyP50Ms' => $this->percentile($turns->pluck('duration_ms')->filter()->all(), 0.50),
        ];

        Log::info('ai-tutor-metrics', $this->embedded($metrics, $day));

        $this->table(['metric', 'value'], collect($metrics)
            ->map(fn ($v, $k) => [$k, is_float($v) ? sprintf('%.6f', $v) : (string) $v])
            ->values()
            ->all());

        return self::SUCCESS;
    }

    /**
     * CloudWatch Embedded Metric Format. CloudWatch parses the `_aws` block out
     * of the log event and creates the metrics automatically; anything outside
     * it stays readable as an ordinary log line.
     */
    private function embedded(array $metrics, Carbon $day): array
    {
        return [
            '_aws' => [
                'Timestamp' => $day->getTimestampMs(),
                'CloudWatchMetrics' => [[
                    'Namespace' => 'Eduvidra/AiTutor',
                    'Dimensions' => [['Environment']],
                    'Metrics' => [
                        ['Name' => 'Turns', 'Unit' => 'Count'],
                        ['Name' => 'InputTokens', 'Unit' => 'Count'],
                        ['Name' => 'CacheWriteTokens', 'Unit' => 'Count'],
                        ['Name' => 'CacheReadTokens', 'Unit' => 'Count'],
                        ['Name' => 'OutputTokens', 'Unit' => 'Count'],
                        ['Name' => 'CostUsd', 'Unit' => 'None'],
                        ['Name' => 'LatencyP95Ms', 'Unit' => 'Milliseconds'],
                        ['Name' => 'LatencyP50Ms', 'Unit' => 'Milliseconds'],
                    ],
                ]],
            ],
            'Environment' => app()->environment(),
            'Date' => $day->toDateString(),
            ...$metrics,
        ];
    }

    /**
     * Nearest-rank percentile. Exact on the small daily samples this reports
     * on, where interpolation would only add spurious precision.
     */
    private function percentile(array $values, float $p): int
    {
        if ($values === []) {
            return 0;
        }

        sort($values);
        $rank = (int) ceil($p * count($values)) - 1;

        return (int) $values[max(0, $rank)];
    }
}
