<?php

namespace App\Enums;

enum SendTemplateType: string {
    case PUSH_MAIL = 'email';
    case PUSH_MOBILE = 'mobile';
    case TELEGRAM_ONLY = 'tg';
}
