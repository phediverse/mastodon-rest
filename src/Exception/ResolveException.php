<?php

namespace Phediverse\MastodonRest\Exception;

use GuzzleHttp\Exception\ClientException;

class ResolveException extends \InvalidArgumentException
{
    public function assertWrapsClientException(int $matchStatus = null) : bool
    {
        if (($e = $this->getPrevious()) && $e instanceof ClientException) {
            if (!$matchStatus || $e->getResponse()->getStatusCode() === $matchStatus) {
                return true;
            }
        }
        throw $this;
    }
}
