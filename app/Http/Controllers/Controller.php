<?php

namespace App\Http\Controllers;

use App\Traits\RespondsWithMessages;

abstract class Controller {
    use RespondsWithMessages; // Используется для отправки JsonResponse

    protected static function getControllerClass(): string {
        return static::class;
    }
}
