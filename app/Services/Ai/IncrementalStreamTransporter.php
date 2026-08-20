<?php

namespace App\Services\Ai;

use Anthropic\Core\Implementation\StreamingHttpClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Makes the Anthropic SDK's SSE stream actually stream.
 *
 * The SDK reads the response body in Util::BUF_SIZE (8,192 byte) blocks. Over
 * TLS, PHP's OpenSSL stream fills the requested length before returning rather
 * than handing back whatever has arrived, so an 8 KB read blocks until 8 KB is
 * buffered — or until EOF. A tutor answer's whole SSE body is smaller than that,
 * so every event arrived in a single read *after generation had finished*: the
 * reply was buffered end to end while looking, from the outside, like streaming.
 *
 * Measured against the real API, same prompt, three runs (see
 * docs/ai-chat-tutor.md):
 *
 *   read(256):  13 reads spread over 2.2s
 *   read(8192):  1 read,  spread 0.000s
 *
 * The fix is to cap how much any one read may ask for. Read size is therefore
 * the streaming granularity: 256 bytes is roughly one delta event, which keeps
 * latency below what a reader can notice without making a syscall per token.
 *
 * This lives here rather than in a vendor patch because ChatClient is the seam
 * where Anthropic-specific workarounds belong.
 */
class IncrementalStreamTransporter implements ClientInterface
{
    /**
     * Bytes any single read may request. Larger blocks until that much has
     * accumulated; smaller only costs syscalls.
     */
    public const MAX_READ = 256;

    private ClientInterface $inner;

    public function __construct(ClientInterface $transporter)
    {
        // StreamingHttpClient is what opts Guzzle into `stream => true`; without
        // it the body is downloaded in full before any of this matters.
        $this->inner = new StreamingHttpClient($transporter);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->inner->sendRequest($request);

        return $response->withBody(new IncrementalReadStream($response->getBody()));
    }
}
