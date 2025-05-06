<?php

namespace App\DTO;

class SignaturePayload
{
    public function __construct(
        public int $timestamp,
        public string $signature,
        public string $method,
        public string $path,
        public string $content
    ) {}
}
