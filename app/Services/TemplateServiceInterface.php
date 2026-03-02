<?php

namespace App\Services;

use App\Models\Incident;

interface TemplateServiceInterface
{
    public function prepare(Incident $data, string $template): array;
}
