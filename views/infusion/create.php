<?php

/** @var yii\web\View $this */
/** @var app\models\InfusionSession $model */
/** @var array $beds */

$this->title = 'Sesi Infus Baru';
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title"><?= $this->title ?></h1>
        <p class="app-page-sub">Masukkan parameter infus; durasi dan perkiraan habis dihitung otomatis.</p>
    </div>
</div>
<div class="app-surface app-surface-pad" style="max-width:720px">
    <?= $this->render('_form', ['model' => $model, 'beds' => $beds, 'roomId' => $roomId ?? null]) ?>
</div>
