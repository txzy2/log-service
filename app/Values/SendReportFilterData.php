<?php

namespace App\Values;

final class SendReportFilterData
{
  public function __construct(
    public readonly string $service,
    public readonly string $code,
    public readonly string $date,
    public readonly int $offset,
    public readonly int $limit,
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      service: $data["service"] ?? "",
      code: $data["code"] ?? "",
      date: $data["date"] ?? "",
      offset: $data[""] ?? 0,
      limit: $data["limit"] ?? 10,
    );
  }
}
