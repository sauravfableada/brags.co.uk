<?php

namespace WeDevs\DokanPro\Dependencies\GuzzleHttp;

use WeDevs\DokanPro\Dependencies\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
