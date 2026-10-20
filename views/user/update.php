<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\helpers\Html;

$this->title = 'Ubah Pengguna · ' . $model->username;
?>
<div class="app-page-header">
    <div><h1 class="app-page-title"><?= Html::encode($this->title) ?></h1></div>
</div>
<div class="app-surface app-surface-pad" style="max-width:640px">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
