<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'di' => [
            'yiisoft/cache' => [
                'config/di.php',
            ],
            'yiisoft/router-fastroute' => [
                'config/di.php',
            ],
            'yiisoft/yii-event' => [
                'config/di.php',
            ],
            'yiisoft/router' => [
                'config/di.php',
            ],
            'yiisoft/aliases' => [
                'config/di.php',
            ],
            '/' => [
                'common/*.php',
            ],
        ],
        'params' => [
            'yiisoft/router-fastroute' => [
                'config/params.php',
            ],
            'yiisoft/yii-db-migration' => [
                'config/params.php',
            ],
            'yiisoft/aliases' => [
                'config/params.php',
            ],
            'yiisoft/yii-console' => [
                'config/params.php',
            ],
            '/' => [
                'params.php',
            ],
        ],
        'di-web' => [
            'yiisoft/router-fastroute' => [
                'config/di-web.php',
            ],
            'yiisoft/yii-event' => [
                'config/di-web.php',
            ],
            '/' => [
                '$di',
                'web/*.php',
            ],
        ],
        'di-console' => [
            'yiisoft/yii-event' => [
                'config/di-console.php',
            ],
            'yiisoft/yii-db-migration' => [
                'config/di-console.php',
            ],
            'yiisoft/yii-console' => [
                'config/di-console.php',
            ],
            '/' => [
                '$di',
                'console/*.php',
            ],
        ],
        'events-console' => [
            'yiisoft/yii-console' => [
                'config/events-console.php',
            ],
            '/' => [
                '$events',
            ],
        ],
        'params-web' => [
            '/' => [
                '$params',
            ],
        ],
        'params-console' => [
            '/' => [
                '$params',
            ],
        ],
        'bootstrap' => [
            '/' => [
                '?bootstrap.php',
            ],
        ],
        'bootstrap-web' => [
            '/' => [
                '$bootstrap',
            ],
        ],
        'bootstrap-console' => [
            '/' => [
                '$bootstrap',
            ],
        ],
        'events' => [
            '/' => [],
        ],
        'events-web' => [
            '/' => [
                '$events',
            ],
        ],
        'routes' => [
            '/' => [
                'routes.php',
            ],
        ],
    ],
];
