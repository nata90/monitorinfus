<?php

/** @var yii\web\View $this */
/** @var app\models\Bed[] $beds */
/** @var string $q */
/** @var array $rooms [id => nama] */
/** @var int|null $roomId */

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Bed';
$identity = Yii::$app->user->identity;
$isAdmin = $identity instanceof User && $identity->getIsAdmin();
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title">Daftar Bed</h1>
        <p class="app-page-sub"><?= count($beds) ?> bed<?= $roomId && isset($rooms[$roomId]) ? ' di ' . Html::encode($rooms[$roomId]) : ' terdaftar' ?></p>
    </div>
    <div class="app-page-actions">
        <form method="get" action="<?= Url::to(['bed/index']) ?>" class="app-inline app-form" role="search">
            <select name="room" class="form-select app-select-sm" aria-label="Filter ruangan" onchange="this.form.submit()">
                <option value="">Semua ruangan</option>
                <?php foreach ($rooms as $id => $name): ?>
                    <option value="<?= $id ?>"<?= (int) $roomId === (int) $id ? ' selected' : '' ?>><?= Html::encode($name) ?></option>
                <?php endforeach ?>
            </select>
            <label class="app-search">
                <i class="mdi mdi-magnify" aria-hidden="true"></i>
                <input type="search" name="q" value="<?= Html::encode($q) ?>" placeholder="Cari kode / ruangan…" aria-label="Cari bed">
            </label>
        </form>
        <?php if ($isAdmin): ?>
            <?= Html::a('<i class="mdi mdi-plus" aria-hidden="true"></i> Tambah Bed', ['bed/create', 'room' => $roomId], ['class' => 'app-btn app-btn-primary']) ?>
        <?php endif ?>
    </div>
</div>

<div class="app-surface">
    <div class="app-table-wrap">
        <table class="app-table">
            <thead>
            <tr>
                <th>Kode</th>
                <th>Ruangan</th>
                <th>Keterangan</th>
                <th>Infus saat ini</th>
                <th>Status bed</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$beds): ?>
                <tr><td colspan="6" class="app-table-empty"><i class="mdi mdi-bed-empty" aria-hidden="true"></i>Tidak ada bed<?= $q !== '' ? ' yang cocok dengan "' . Html::encode($q) . '"' : '' ?>.</td></tr>
            <?php endif ?>
            <?php foreach ($beds as $bed): $s = $bed->activeSession; ?>
                <tr>
                    <td class="cell-strong"><?= Html::encode($bed->code) ?></td>
                    <td>
                        <?= Html::encode($bed->getRoomName()) ?>
                        <?php if ($bed->room && !$bed->room->is_active): ?><span class="app-badge app-badge-neutral">ruangan nonaktif</span><?php endif ?>
                    </td>
                    <td class="app-muted"><?= Html::encode($bed->description ?: '-') ?></td>
                    <td>
                        <?php if ($s): $d = $s->getDisplayStatus(); ?>
                            <span class="app-badge app-badge-<?= $d ?>"><?= Html::encode($s->getDisplayLabel()) ?></span>
                            <span class="app-muted num">· <?= $s->getRemainingMinutes() ?> mnt</span>
                        <?php else: ?>
                            <span class="app-muted">—</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($bed->is_active): ?>
                            <span class="app-badge app-badge-primary"><i class="mdi mdi-check" aria-hidden="true"></i>Aktif</span>
                        <?php else: ?>
                            <span class="app-badge app-badge-neutral"><i class="mdi mdi-minus" aria-hidden="true"></i>Nonaktif</span>
                        <?php endif ?>
                    </td>
                    <td class="cell-actions">
                        <?= Html::a('<i class="mdi mdi-history" aria-hidden="true"></i> Riwayat', ['bed/history', 'id' => $bed->id], ['class' => 'app-btn app-btn-sm app-btn-secondary']) ?>
                        <?php if ($isAdmin): ?>
                            <?= Html::a('<i class="mdi mdi-pencil-outline" aria-hidden="true"></i>', ['bed/update', 'id' => $bed->id], ['class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon', 'title' => 'Ubah', 'aria-label' => 'Ubah']) ?>
                            <?= Html::a(
                                '<i class="mdi ' . ($bed->is_active ? 'mdi-power' : 'mdi-power-plug-outline') . '" aria-hidden="true"></i>',
                                ['bed/toggle', 'id' => $bed->id],
                                [
                                    'class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon',
                                    'title' => $bed->is_active ? 'Nonaktifkan' : 'Aktifkan',
                                    'aria-label' => $bed->is_active ? 'Nonaktifkan' : 'Aktifkan',
                                    'data-method' => 'post',
                                    'data-confirm' => ($bed->is_active ? 'Nonaktifkan' : 'Aktifkan') . ' bed ' . $bed->code . '?',
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
