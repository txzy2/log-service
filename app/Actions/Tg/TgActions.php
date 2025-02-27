<?php

namespace App\Actions\Tg;

class  TgActions
{

    public function start(): void
    {
        \Illuminate\Support\Facades\Log::channel('debug')->info('works');
    }
}
