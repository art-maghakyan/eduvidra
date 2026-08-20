<?php

namespace Tests\Feature;

use App\Services\Ai\IncrementalReadStream;
use App\Services\Ai\IncrementalStreamTransporter;
use GuzzleHttp\Psr7\Utils;
use Tests\TestCase;

/**
 * Guards the fix that makes the Anthropic SDK's SSE stream actually stream.
 *
 * The SDK asks for 8,192 bytes at a time. Over TLS, PHP fills that buffer
 * before returning, so a whole tutor reply — comfortably under 8 KB — arrived
 * in one read after generation had already finished. Capping the read size is
 * the difference between a reply that appears word by word and one that appears
 * all at once several seconds late.
 */
class IncrementalStreamTest extends TestCase
{
    public function test_a_large_read_is_capped_so_the_stream_cannot_block_on_a_full_buffer(): void
    {
        $stream = new IncrementalReadStream(Utils::streamFor(str_repeat('a', 8192)));

        // The SDK's Util::BUF_SIZE, verbatim.
        $this->assertSame(IncrementalStreamTransporter::MAX_READ, strlen($stream->read(8192)));
    }

    public function test_a_small_read_is_left_alone(): void
    {
        $stream = new IncrementalReadStream(Utils::streamFor(str_repeat('a', 8192)));

        $this->assertSame(16, strlen($stream->read(16)));
    }

    public function test_the_whole_body_still_arrives_in_order(): void
    {
        $body = str_repeat('abcdefgh', 1000);
        $stream = new IncrementalReadStream(Utils::streamFor($body));

        $read = '';
        while (! $stream->eof()) {
            $read .= $stream->read(8192);
        }

        // Capping reads must change the timing, never the content.
        $this->assertSame($body, $read);
    }
}
