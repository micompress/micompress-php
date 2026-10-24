<?php

namespace MiCompress;

class CompressException extends \RuntimeException
{
    /** @var string Machine-readable error code, e.g. UNSUPPORTED, LOGIN_REQUIRED, NETWORK. */
    private $errorCode;

    public function __construct(string $message, string $errorCode = 'API_ERROR')
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
