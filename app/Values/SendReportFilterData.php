<?php

namespace App\Values;

class SendReportFilterData
{
  public function __construct(
    public readonly string $service,
    public readonly string $code,
    public readonly string $date,
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      service: $data["service"] ?? "",
      code: $data["code"] ?? "",
      date: $data["date"] ?? "",
    );
  }
}
