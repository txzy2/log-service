<?php

namespace App\Services;

use App\Models\IncidentType;
use App\Values\AddTypeData;
use Illuminate\Support\Facades\Log;

class IncidentTypeService
{
  public function create(AddTypeData $data): array
  {
    $existType = IncidentType::where('code', $data->code)
      ->orWhere('type_name', $data->typeName)
      ->first();

    if ($existType) {
      return [
        'success' => false,
        'message' => 'Такой тип ошибки уже существует',
        'data' => []
      ];
    }

    try {
      $newType = IncidentType::create([
        'type_name' => $data->typeName,
        'code' => $data->code,
        'send_template_id' => $data->sendTemplateId,
        'lifecycle' => $data->lifecycle,
        'alias' => $data->sendTo,
      ]);

      Log::channel('debug')->info('IncidentType created', [$newType]);

      return [
        'success' => true,
        'message' => 'Тип успешно создан',
        'data' => $newType->toArray()
      ];
    } catch (\Exception $e) {
      Log::error('Failed to create IncidentType', [
        'error' => $e->getMessage(),
        'data' => $data
      ]);

      return [
        'success' => false,
        'message' => 'Ошибка сохранения типа',
        'data' => []
      ];
    }
  }
}
