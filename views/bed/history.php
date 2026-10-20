<?php

/** @var yii\web\View $this */
/** @var app\models\Bed $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $filters */

use app\models\InfusionSession;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Riwayat · ' . $model->code;
$fmt = Yii::$app->formatter;
/** @var InfusionSession[] $sessions */
$sessions = $dataProvider->getModels();
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title">Riwayat Infus · <?= Html::encode($model->code) ?></h1>
        <p class="app-page-sub"><?= Html::encode($model->getRoomName()) ?> · <?= $dataProvider->getTotalCount() ?> sesi</p>
    </div>
    <div class="app-page-actions">
        <?= Html::a('<i class="mdi mdi-arrow-left" aria-hidden="true"></i> Daftar bed', ['bed/index'], ['class' => 'app-btn app-btn-secondary']) ?>
        <?php if ($model->is_active && !$model->activeSession): ?>
            <?= Html::a('<i class="mdi mdi-plus" aria-hidden="true"></i> Sesi Baru', ['infusion/create', 'bed_id' => $model->id], ['class' => 'app-btn app-btn-primary', 'data-modal-url' => Url::to(['infusion/create', 'bed_id' => $model->id])]) ?>
        <?php endif ?>
    </div>
</div>

<form method="get" action="<?= Url::to(['bed/history', 'id' => $model->id]) ?>" class="app-form app-filter-bar">
    <div>
        <label class="form-label" for="f-status">Status</label>
        <select class="form-select" id="f-status" name="status">
            <option value="">Semua</option>
            <?php foreach (InfusionSession::statusLabels() as $k => $label): ?>
                <option value="<?= $k ?>"<?= $filters['status'] === $k ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div>
        <label class="form-label" for="f-from">Dari tanggal</label>
        <input type="date" class="form-control" id="f-from" name="from" value="<?= Html::encode($filters['from']) ?>">
    </div>
    <div>
        <label class="form-label" for="f-to">Sampai tanggal</label>
        <input type="date" class="form-control" id="f-to" name="to" value="<?= Html::encode($filters['to']) ?>">
    </div>
    <button type="submit" class="app-btn app-btn-secondary"><i class="mdi mdi-filter-outline" aria-hidden="true"></i> Terapkan</button>
    <?php if ($filters['status'] !== '' || $filters['from'] !== '' || $filters['to'] !== ''): ?>
        <?= Html::a('Reset', ['bed/history', 'id' => $model->id], ['class' => 'app-btn app-btn-ghost']) ?>
    <?php endif ?>
</form>

<div class="app-surface">
    <div class="app-table-wrap">
        <table class="app-table">
            <thead>
            <tr>
                <th>Mulai</th>
                <th>Perkiraan habis</th>
                <th>Durasi</th>
                <th>Faktor tetes</th>
                <th>Volume</th>
                <th>TPM</th>
                <th>Status</th>
                <th>Selesai/Dihentikan</th>
                <th>Petugas</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$sessions): ?>
                <tr><td colspan="10" class="app-table-empty"><i class="mdi mdi-history" aria-hidden="true"></i>Belum ada riwayat sesi.</td></tr>
            <?php endif ?>
            <?php foreach ($sessions as $s): $d = $s->getDisplayStatus(); ?>
                <tr>
                    <td class="num"><?= $fmt->asDatetime($s->started_at) ?></td>
                    <td class="num"><?= $fmt->asDatetime($s->estimated_end_at) ?></td>
                    <td class="num"><?= $fmt->asDecimal($s->duration_minutes, 1) ?> mnt</td>
                    <td class="num"><?= $fmt->asDecimal($s->drop_factor, 2) ?></td>
                    <td class="num"><?= $fmt->asDecimal($s->volume_ml, 2) ?> mL</td>
                    <td class="num"><?= $fmt->asDecimal($s->tpm, 2) ?></td>
                    <td><span class="app-badge app-badge-<?= $d ?>"><?= Html::encode($s->getDisplayLabel()) ?></span></td>
                    <td class="num"><?= $fmt->asDatetime($s->completed_at) ?></td>
                    <td class="app-muted">
                        <?= Html::encode($s->creator->username ?? '-') ?>
                        <?php if ($s->updated_by && $s->updated_by !== $s->created_by): ?>
                            <br><small>diubah: <?= Html::encode($s->updater->username ?? '-') ?></small>
                        <?php endif ?>
                    </td>
                    <td class="cell-actions">
                        <?= Html::a('<i class="mdi mdi-eye-outline" aria-hidden="true"></i>', ['infusion/view', 'id' => $s->id], ['class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon', 'title' => 'Detail', 'aria-label' => 'Detail']) ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php if ($dataProvider->getPagination()->getPageCount() > 1): ?>
        <div class="app-pagination">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->getPagination(),
                'options' => ['class' => 'pagination'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
                'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
                'prevPageLabel' => '<i class="mdi mdi-chevron-left"></i>',
                'nextPageLabel' => '<i class="mdi mdi-chevron-right"></i>',
            ]) ?>
        </div>
    <?php endif ?>
</div>
