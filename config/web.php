<?php

require_once __DIR__ . '/env.php';

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'infus',
    'name' => $params['appName'],
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'id-ID',
    'timeZone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => env('COOKIE_VALIDATION_KEY', 'ubah-nilai-ini-di-file-env-agar-aman'),
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],
        'session' => [
            'name' => 'INFUSSESSID',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'formatter' => [
            'class' => \yii\i18n\Formatter::class,
            'locale' => 'id-ID',
            // Nilai datetime di DB disimpan dalam zona waktu aplikasi, bukan UTC.
            'defaultTimeZone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
            'timeZone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
            // Format numerik agar konsisten tanpa ekstensi intl.
            'dateFormat' => 'php:d/m/Y',
            'timeFormat' => 'php:H:i',
            'datetimeFormat' => 'php:d/m/Y H:i',
            'decimalSeparator' => ',',
            'thousandSeparator' => '.',
            'nullDisplay' => '-',
        ],
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            // true agar berjalan tanpa mod_rewrite/.htaccess (AllowOverride None).
            // Set URL_SHOW_SCRIPT_NAME=false bila server sudah mendukung rewrite.
            'showScriptName' => env('URL_SHOW_SCRIPT_NAME', true),
            'rules' => [
                '' => 'dashboard/index',
                'dashboard' => 'dashboard/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'api/infusions/active' => 'dashboard/data',
                'bed/<id:\d+>/history' => 'bed/history',
                'infusion/<id:\d+>/complete' => 'infusion/complete',
                'infusion/<id:\d+>/stop' => 'infusion/stop',
                'infusion/<id:\d+>/update' => 'infusion/update',
                '<controller:[\w-]+>/<id:\d+>' => '<controller>/view',
                '<controller:[\w-]+>/<action:[\w-]+>/<id:\d+>' => '<controller>/<action>',
                '<controller:[\w-]+>/<action:[\w-]+>' => '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        'allowedIPs' => ['127.0.0.1', '::1', '172.17.0.*', '192.168.*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        'allowedIPs' => ['127.0.0.1', '::1', '172.17.0.*', '192.168.*'],
    ];
}

return $config;
