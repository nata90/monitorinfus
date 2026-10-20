<?php

/**
 * Koneksi ke container MySQL yang sudah ada.
 * Seluruh nilai dibaca dari environment variable (lihat .env.example).
 *
 * Jika aplikasi berjalan DI DALAM container (mis. container webapp), host/port
 * dapat dibedakan lewat DB_HOST_DOCKER / DB_PORT_DOCKER karena 127.0.0.1 di
 * dalam container bukan mesin host.
 */

$inDocker = is_file('/.dockerenv');

$host = $inDocker ? env('DB_HOST_DOCKER', env('DB_HOST', '127.0.0.1')) : env('DB_HOST', '127.0.0.1');
$port = $inDocker ? env('DB_PORT_DOCKER', env('DB_PORT', '3306')) : env('DB_PORT', '3306');
$name = env('DB_NAME', 'infus');

/*return [
    'class' => \yii\db\Connection::class,
    'dsn' => "mysql:host={$host};port={$port};dbname={$name}",
    'username' => env('DB_USER', 'root'),
    'password' => (string) env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'enableSchemaCache' => !YII_DEBUG,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];*/

return [
    'class' => \yii\db\Connection::class,
    'dsn' => "mysql:host=192.168.0.193;port=3306;dbname=infus",
    'username' => 'sinergis',
    'password' => '1qazxsw2',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => !YII_DEBUG,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
