<?php

namespace App\Enums;

enum ErrorsEnum: int
{
    case SUCCESS = 200;
    case ERROR_WRITE_INCIDENT = 400;
    case VALIDATION_ERROR = 422;
    case NOT_FOUND = 404;
    case BAD_REQUEST = 403;

    public function getMessage(): string
    {
        return match ($this) {
            self::SUCCESS => 'OK',
            self::ERROR_WRITE_INCIDENT => 'Ошибка записи',
            self::VALIDATION_ERROR => 'Не заполнены обязательные поля',
            self::BAD_REQUEST => 'Неверный запрос. Проверьте отправленные данные'
        };
    }
}
