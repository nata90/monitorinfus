<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;
use yii\web\HttpException;

$this->title = $name;
$statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;

if (Yii::$app->user->isGuest) {
    $this->context->layout = 'auth';
}
?>
<div class="app-error">
    <div class="app-error-code num"><?= Html::encode($statusCode) ?></div>
    <h1><?= Html::encode($message) ?></h1>
    <p class="app-muted">Terjadi kesalahan saat memproses permintaan. Jika berulang, hubungi administrator.</p>
    <?= Html::a('<i class="mdi mdi-arrow-left" aria-hidden="true"></i> Kembali ke dashboard', Yii::$app->homeUrl, ['class' => 'app-btn app-btn-primary']) ?>
</div>
