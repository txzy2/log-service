<?php

namespace App\Enums;

enum SendTemplateType: string
{
    case PUSH_MAIL = 'manager';
    case PUSH_MOBILE = 'mobile';
    case TELEGRAM_ONLY = 'tg';
}
