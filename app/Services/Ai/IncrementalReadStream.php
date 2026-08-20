<?php

namespace App\Services\Ai;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * A response body that refuses to be read in large blocks.
 *
 * See IncrementalStreamTransporter for why this exists. The property is
 * declared rather than left to StreamDecoratorTrait's magic __get, which would
 * otherwise create it dynamically — deprecated as of PHP 8.4.
 */
class IncrementalReadStream implements StreamInterface
{
    use StreamDecoratorTrait;

    protected StreamInterface $stream;

    public function read(int $length): string
    {
        return $this->stream->read(min($length, IncrementalStreamTransporter::MAX_READ));
    }
}
