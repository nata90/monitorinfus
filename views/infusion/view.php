<?php

/** @var yii\web\View $this */
/** @var app\models\InfusionSession $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Detail Sesi · ' . $model->bed->code;
$fmt = Yii::$app->formatter;
$display = $model->getDisplayStatus();
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title"><?= Html::encode($this->title) ?></h1>
        <p class="app-page-sub"><?= Html::encode($model->bed->getRoomName()) ?></p>
    </div>
    <div class="app-page-actions">
        <?= Html::a('<i class="mdi mdi-history" aria-hidden="true"></i> Riwayat bed', ['bed/history', 'id' => $model->bed_id], ['class' => 'app-btn app-btn-secondary']) ?>
        <?php if ($model->getIsActive()): ?>
            <?= Html::a('<i class="mdi mdi-pencil-outline" aria-hidden="true"></i> Ubah', ['infusion/update', 'id' => $model->id], ['class' => 'app-btn app-btn-primary']) ?>
        <?php endif ?>
    </div>
</div>

<div class="app-surface app-surface-pad app-stack">
    <div class="app-inline">
        <span class="app-badge app-badge-<?= $display ?>"><?= Html::encode($model->getDisplayLabel()) ?></span>
        <?php if ($model->getIsActive()): ?>
            <span class="app-muted">sisa <strong class="num"><?= $model->getRemainingMinutes() ?></strong> menit</span>
        <?php endif ?>
    </div>

    <div class="app-detail-grid">
        <div><div class="app-detail-label">Faktor tetes</div><div class="app-detail-value"><?= $fmt->asDecimal($model->drop_factor, 2) ?> tts/mL</div></div>
        <div><div class="app-detail-label">Volume</div><div class="app-detail-value"><?= $fmt->asDecimal($model->volume_ml, 2) ?> mL</div></div>
        <div><div class="app-detail-label">TPM</div><div class="app-detail-value"><?= $fmt->asDecimal($model->tpm, 2) ?></div></div>
        <div><div class="app-detail-label">Durasi</div><div class="app-detail-value"><?= $fmt->asDecimal($model->duration_minutes, 1) ?> menit</div></div>
        <div><div class="app-detail-label">Mulai</div><div class="app-detail-value"><?= $fmt->asDatetime($model->started_at) ?></div></div>
        <div><div class="app-detail-label">Perkiraan habis</div><div class="app-detail-value"><?= $fmt->asDatetime($model->estimated_end_at) ?></div></div>
        <div><div class="app-detail-label">Diselesaikan</div><div class="app-detail-value"><?= $fmt->asDatetime($model->completed_at) ?></div></div>
        <div><div class="app-detail-label">Status</div><div class="app-detail-value"><?= Html::encode($model->getStatusLabel()) ?></div></div>
        <div><div class="app-detail-label">Dibuat oleh</div><div class="app-detail-value"><?= Html::encode($model->creator->username ?? '-') ?> <small class="app-muted"><?= $fmt->asDatetime($model->created_at) ?></small></div></div>
        <div><div class="app-detail-label">Diubah oleh</div><div class="app-detail-value"><?= Html::encode($model->updater->username ?? '-') ?> <small class="app-muted"><?= $fmt->asDatetime($model->updated_at) ?></small></div></div>
    </div>

    <?php if ($model->notes): ?>
        <div><div class="app-detail-label">Catatan</div><div><?= nl2br(Html::encode($model->notes)) ?></div></div>
    <?php endif ?>

    <?php if ($model->getIsActive()): ?>
        <div class="app-form-actions" style="justify-content:flex-start">
            <?= Html::beginForm(['infusion/complete', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
                <?= Html::hiddenInput('returnUrl', Url::current()) ?>
                <button type="submit" class="app-btn app-btn-safe" data-confirm="Tandai sesi ini sebagai selesai?"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i> Tandai Selesai</button>
            <?= Html::endForm() ?>
            <?= Html::beginForm(['infusion/stop', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
                <?= Html::hiddenInput('returnUrl', Url::current()) ?>
                <button type="submit" class="app-btn app-btn-danger" data-confirm="Hentikan sesi infus ini?"><i class="mdi mdi-stop-circle-outline" aria-hidden="true"></i> Hentikan</button>
            <?= Html::endForm() ?>
        </div>
    <?php endif ?>
</div>
