<?php

namespace app\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Asset utama: Poppins (self-host), Material Design Icons (self-host), tema aplikasi.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'static/fonts/poppins/poppins.css',
        'static/mdi/css/materialdesignicons.min.css',
        'css/app.css',
    ];
    public $js = [
        'js/app.js',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        BootstrapPluginAsset::class,
    ];
}
