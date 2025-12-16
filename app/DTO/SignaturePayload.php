<?php

namespace App\DTO;

class SignaturePayload
{
    public function __construct(
        public readonly int $timestamp,
        public readonly string $signature,
        public readonly string $method,
        public readonly string $path,
        public readonly string $content
    ) {}
}
