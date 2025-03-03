<?php

use App\Actions\Tg\TgActions;
use Telegram\Bot\Commands\HelpCommand;

return [
    'bots' => [
        'mybot' => [
            'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            // 'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),
            'http_client_config' => [
                'verify' => 'C:/php/cacert.pem',
            ],
            // 'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            /*
             * @see https://core.telegram.org/bots/api#update
             */
            'allowed_updates' => null,
            'commands' => [
                //Acme\Project\Commands\MyTelegramBot\BotCommand::class
            ],
        ],

        //        'mySecondBot' => [
        //            'token' => '123456:abc',
        //        ],
    ],

    'default' => 'mybot',
    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    'http_client_handler' => null,
    'base_bot_url' => null,
    'resolve_command_dependencies' => true,
    'commands' => [
        HelpCommand::class,
    ],
    'command_groups' => [
        /* // Group Type: 1
           'commmon' => [
                Acme\Project\Commands\TodoCommand::class,
                Acme\Project\Commands\TaskCommand::class,
           ],
        */

        // Group Type: 2
        'subscription' => [
            'start', // Shared Command Name.
            'stop', // Shared Command Name.
        ],


        /* // Group Type: 3
            'auth' => [
                Acme\Project\Commands\LoginCommand::class,
                Acme\Project\Commands\SomeCommand::class,
            ],

            'stats' => [
                Acme\Project\Commands\UserStatsCommand::class,
                Acme\Project\Commands\SubscriberStatsCommand::class,
                Acme\Project\Commands\ReportsCommand::class,
            ],

            'admin' => [
                'auth', // Command Group Name.
                'stats' // Command Group Name.
            ],
        */

        // Group Type: 4
        'myBot' => [
            'admin', // Command Group Name.
            'subscription', // Command Group Name.
            'status', // Shared Command Name.
            'Acme\Project\Commands\BotCommand', // Full Path to Command Class.
            'start'
        ],
    ],

    'shared_commands' => [
        'start' => TgActions::class,
        // 'stop' => Acme\Project\Commands\StopCommand::class,
        // 'status' => Acme\Project\Commands\StatusCommand::class,
    ],
];
