<?php

/** @var yii\web\View $this */
/** @var app\models\InfusionSession $model */
/** @var array $beds */

$this->title = 'Ubah Sesi Infus · ' . $model->bed->code;
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title"><?= \yii\helpers\Html::encode($this->title) ?></h1>
        <p class="app-page-sub">Perubahan parameter akan menghitung ulang durasi dan perkiraan habis.</p>
    </div>
</div>
<div class="app-surface app-surface-pad" style="max-width:720px">
    <?= $this->render('_form', ['model' => $model, 'beds' => $beds, 'roomId' => $roomId ?? null]) ?>
</div>
