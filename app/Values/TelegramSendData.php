<?php

namespace App\Values;

class TelegramSendData
{
    public function __construct(
        public readonly string $class,
        public readonly string $title,
        public readonly string $text,
        public readonly ?array $additionalInfo = []
    ) {
    }
}
