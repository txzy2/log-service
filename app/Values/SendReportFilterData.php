<?php

namespace App\Values;

class SendReportFilterData
{
  public function __construct(
    public readonly string $service,
    public readonly string $source,
    public readonly string $code,
    public readonly int $date,
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      service: $data["service"],
      source: $data["source"],
      code: $data["code"],
      date: $data["date"],
    );
  }
}
