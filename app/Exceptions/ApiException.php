<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/** Galat yang pesannya aman ditampilkan ke extension: {"error": "..."} */
class ApiException extends Exception
{
    public function __construct(string $message, private int $status = 400)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage()], $this->status, [], JSON_UNESCAPED_UNICODE);
    }
}
