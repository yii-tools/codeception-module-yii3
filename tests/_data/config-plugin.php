<?php

declare(strict_types=1);

return [
    'config-plugin' => [
        'application-params' => '?application-params.php',
        'di' => [
            'common/*.php',
            '../../../vendor/yii-tools/skeleton-app/config/common/*.php',
        ],
        'di-web' => [
            '$di',
            '../../../vendor/yii-tools/skeleton-app/config/web/*.php',
        ],
        'di-console' => [
            '$di',
            'console/*.php',
        ],
        'params' => [
            'common/param/*.php',
            '../../../vendor/yii-tools/skeleton-app/config/common/param/*.php',
        ],
        'params-console' => [
            '$params',
            'console/param/*.php',
        ],
        'params-web' => [
            '$params',
            'web/param/*.php',
            '../../../vendor/yii-tools/skeleton-app/config/web/param/*.php',
        ],
    ],
    'config-plugin-options' => [
        'source-directory' => 'tests/_data/config',
    ],
];
