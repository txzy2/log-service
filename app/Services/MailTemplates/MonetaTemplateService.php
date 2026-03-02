<?php

namespace App\Services\MailTemplates;

use App\Enums\ErrorsEnum;
use App\Models\Incident;
use App\Services\TemplateServiceInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonetaTemplateService implements TemplateServiceInterface
{
    public function prepare(Incident $data, string $template): array
    {
        $return = [
            "success" => false,
            "data"    => "",
        ];

        try {
            $additionalFields = $data->additionalFields ?? [];

            $fields = [];
            foreach ($additionalFields as $item) {
                if (is_array($item) && isset($item['key'], $item['value'])) {
                    $fields[$item['key']] = $item['value'];
                }
            }

            if (isset($fields['transit'])) {
                $replacements = [
                    '{{inn}}'      => $fields['inn'] ?? '',
                    '{{kpp}}'      => $fields['kpp'] ?? '',
                    '{{bank_acc}}' => $fields['bank_acc'] ?? '',
                    '{{transit}}'  => $fields['transit'] ?? '',
                ];

                $return['data'] = str_replace(array_keys($replacements), array_values($replacements), $template);
                $return['success'] = true;
            } else {
                $return['data'] = ErrorsEnum::NOT_FOUND_ADDITIONAL_INFO->getMessage();
            }

        } catch (Throwable $e) {
            Log::channel('debug')->info("prepared prepare ERROR", [$e->getMessage()]);
        }

        return $return;

    }
}
