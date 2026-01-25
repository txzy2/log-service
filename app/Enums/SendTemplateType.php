<?php

namespace App\Enums;

enum SendTemplateType: string {
    case PUSH_MAIL = 'Email';
    case PUSH_MOBILE = 'Mobile';
    case TELEGRAM_ONLY = 'Tg';
}
