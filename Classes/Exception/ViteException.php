<?php

namespace Larsgowebdev\ViteAssetCollectorWp\Exception;

use Exception;

class ViteException extends Exception
{
    public function __construct(
        string $message = "",
        ?string $suggestion = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $fullMessage = $message;
        if ($suggestion) {
            $fullMessage .= "\nSuggestion: " . $suggestion;
        }
        parent::__construct($fullMessage, $code, $previous);
    }
}