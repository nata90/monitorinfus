<?php

// Ganti ke false / 'prod' saat produksi, atau atur lewat environment variable.
$debug = getenv('YII_DEBUG');
$env = getenv('YII_ENV');

defined('YII_DEBUG') or define('YII_DEBUG', $debug === false ? true : filter_var($debug, FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', $env === false ? 'dev' : $env);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
