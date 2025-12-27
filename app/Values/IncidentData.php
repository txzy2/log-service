<?php

namespace App\Values;

use App\Helpers\Parsers\Parser;

class IncidentData
{
    public function __construct(
        public readonly string $level,
        public readonly string $service,
        public string $message,
        public readonly string $domain,
        public readonly string $action,
        public readonly string $function,
        public readonly ?array $additionalFields = null,
        public readonly string $file = '',
        public readonly string $class = '',
        public readonly string $date,
        public readonly string $hashSum
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            level: $data['level'],
            service: $data['service'],
            message: $data['message'],
            domain: $data['domain'],
            action: $data['action'],
            function: $data['function'],
            additionalFields: $data['additionalFields'] ?? null,
            file: $data['file'] ?? '',
            class: $data['class'] ?? '',
            date: $data['date'],
            hashSum: $data['hash_sum']
        );
    }

    public function setNewMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * generateHash - генерирует хэш
     *
     * @return string
     */
    protected function generateHash(): string
    {
        return hash(
            'sha256',
            $this->service . $this->action . $this->function . $this->level
        );
    }

    /**
     * isValidHash - Проверяет правильный ли хэш
     *
     * @return boolean
     */
    public function isValidHash(): bool
    {
        return $this->hashSum === $this->generateHash();
    }

    /**
     * parseCodeAndMessage - Парсит сообщение
     *
     * @return array
     */
    public function parseCodeAndMessage(): array
    {
        if ($this->level !== 'info') {
            return Parser::parseStr($this->message);
        }
        return ['', $this->message];
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'service' => $this->service,
            'message' => $this->message,
            'domain' => $this->domain,
            'action' => $this->action,
            'function' => $this->function,
            'file' => $this->file,
            'class' => $this->class,
            'date' => $this->date,
            'hasSum' => $this->hashSum,
            'additionalFields' => !empty($this->additionalFields)
                ? json_encode($this->additionalFields)
                : null,
        ];
    }
}
