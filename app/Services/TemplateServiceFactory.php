<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class TemplateServiceFactory {
    public static function create(string $source): TemplateServiceInterface
    {
        $service = Config::get('mail_templates.sources');
        if (!isset($service[$source])) {
            throw new InvalidArgumentException("Unknown source: {$source}");
        }

        return app($service[$source]);
    }
}
