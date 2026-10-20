<?php

/** @var yii\web\View $this */
/** @var app\models\Room[] $rooms */

use app\models\User;
use yii\helpers\Html;

$this->title = 'Ruangan';
$identity = Yii::$app->user->identity;
$isAdmin = $identity instanceof User && $identity->getIsAdmin();
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title">Master Ruangan</h1>
        <p class="app-page-sub"><?= count($rooms) ?> ruangan · bed melekat pada ruangan; dashboard dapat difilter per ruangan</p>
    </div>
    <?php if ($isAdmin): ?>
        <div class="app-page-actions">
            <?= Html::a('<i class="mdi mdi-plus" aria-hidden="true"></i> Tambah Ruangan', ['room/create'], ['class' => 'app-btn app-btn-primary']) ?>
        </div>
    <?php endif ?>
</div>

<div class="app-surface">
    <div class="app-table-wrap">
        <table class="app-table">
            <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Keterangan</th>
                <th>Bed</th>
                <th>Infus aktif</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rooms): ?>
                <tr><td colspan="7" class="app-table-empty"><i class="mdi mdi-door-open" aria-hidden="true"></i>Belum ada ruangan.</td></tr>
            <?php endif ?>
            <?php foreach ($rooms as $room): $active = $room->getActiveSessionCount(); ?>
                <tr>
                    <td class="cell-strong"><?= Html::encode($room->code) ?></td>
                    <td><?= Html::encode($room->name) ?></td>
                    <td class="app-muted"><?= Html::encode($room->description ?: '-') ?></td>
                    <td class="num"><?= Html::a(count($room->beds) . ' bed', ['bed/index', 'room' => $room->id]) ?></td>
                    <td class="num">
                        <?php if ($active): ?>
                            <?= Html::a('<span class="app-badge app-badge-primary"><i class="mdi mdi-iv-bag" aria-hidden="true"></i>' . $active . ' aktif</span>', ['dashboard/index', 'room' => $room->id], ['title' => 'Buka dashboard ruangan ini']) ?>
                        <?php else: ?>
                            <span class="app-muted">—</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($room->is_active): ?>
                            <span class="app-badge app-badge-safe"><i class="mdi mdi-check" aria-hidden="true"></i>Aktif</span>
                        <?php else: ?>
                            <span class="app-badge app-badge-neutral"><i class="mdi mdi-minus" aria-hidden="true"></i>Nonaktif</span>
                        <?php endif ?>
                    </td>
                    <td class="cell-actions">
                        <?= Html::a('<i class="mdi mdi-view-dashboard-outline" aria-hidden="true"></i> Dashboard', ['dashboard/index', 'room' => $room->id], ['class' => 'app-btn app-btn-sm app-btn-secondary']) ?>
                        <?php if ($isAdmin): ?>
                            <?= Html::a('<i class="mdi mdi-pencil-outline" aria-hidden="true"></i>', ['room/update', 'id' => $room->id], ['class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon', 'title' => 'Ubah', 'aria-label' => 'Ubah']) ?>
                            <?= Html::a(
                                '<i class="mdi ' . ($room->is_active ? 'mdi-power' : 'mdi-power-plug-outline') . '" aria-hidden="true"></i>',
                                ['room/toggle', 'id' => $room->id],
                                [
                                    'class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon',
                                    'title' => $room->is_active ? 'Nonaktifkan' : 'Aktifkan',
                                    'aria-label' => $room->is_active ? 'Nonaktifkan' : 'Aktifkan',
                                    'data-method' => 'post',
                                    'data-confirm' => ($room->is_active ? 'Nonaktifkan' : 'Aktifkan') . ' ruangan ' . $room->name . '?',
                                ]
                            ) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
