<?php

namespace App\Values;

class AddTypeData
{
  public function __construct(
    public readonly string $typeName,
    public readonly string $sendTemplateId,
    public readonly string $code,
    public readonly int $lifecycle,
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      typeName: $data["type_name"],
      sendTemplateId: $data["send_template_id"],
      code: $data["code"],
      lifecycle: $data["lifecycle"],
    );
  }
}
