<?php

namespace App\Values;

final class SignaturePayload
{
    public function __construct(
        public readonly int $timestamp,
        public readonly string $signature,
        public readonly string $method,
        public readonly string $path,
        public readonly string $content
    ) {
        $this->validate();
    }

    private function validate(): void {
        if ($this->timestamp <= 0) {
            throw new \InvalidArgumentException('Timestamp must be positive');
        }

        if (empty($this->signature)) {
            throw new \InvalidArgumentException('Signature cannot be empty');
        }
    }
    public static function fromRequest(\Illuminate\Http\Request $request): self {
        return new self(
            (int) $request->header('X-Timestamp'),
            (string) $request->header('X-Signature'),
            $request->method(),
            $request->path(),
            $request->getContent()
        );
    }
}
