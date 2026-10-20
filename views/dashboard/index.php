<?php

/** @var yii\web\View $this */
/** @var array $payload */
/** @var array $rooms [id => nama ruangan aktif] */
/** @var int|null $roomId ruangan yang difilter (null = semua) */
/** @var DateTimeImmutable $now */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

$roomName = $roomId ? ($payload['room_name'] ?? null) : null;
$this->title = $roomName ? 'Dashboard · ' . $roomName : 'Dashboard';
$createUrl = Url::to(['infusion/create', 'room_id' => $roomId]);

$this->registerJsFile('@web/js/dashboard.js', ['depends' => [\app\assets\AppAsset::class]]);
$this->registerJs('window.INFUS_DASHBOARD = ' . Json::htmlEncode([
    'payload' => $payload,
    'roomId' => $roomId,
    'urls' => [
        'index' => Url::to(['dashboard/index']),
        'data' => Url::to(['dashboard/data', 'room' => $roomId]),
        'create' => $createUrl,
        'update' => Url::to(['infusion/update', 'id' => '__ID__']),
        'complete' => Url::to(['infusion/complete', 'id' => '__ID__']),
        'stop' => Url::to(['infusion/stop', 'id' => '__ID__']),
    ],
]) . ';', View::POS_HEAD);
?>
<div id="dashboard" class="app-dashboard">

    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                Monitoring Infus
                <?php if ($roomName): ?>
                    <span class="app-page-title-room"><i class="mdi mdi-door-open" aria-hidden="true"></i><?= Html::encode($roomName) ?></span>
                <?php endif ?>
            </h1>
            <p class="app-page-sub">
                <?= $roomName ? 'Alarm hanya untuk bed di ruangan ini' : 'Semua ruangan' ?>
                · ambang peringatan <?= (int) $payload['warning_minutes'] ?> menit · alarm <?= (int) $payload['alarm_minutes'] ?> menit
                · <span class="js-sync-info">Dimuat <?= $now->format('H:i') ?></span>
            </p>
        </div>
        <div class="app-page-actions">
            <label class="app-room-picker">
                <i class="mdi mdi-door-open" aria-hidden="true"></i>
                <span class="visually-hidden">Pilih ruangan</span>
                <select class="form-select js-room" aria-label="Pilih ruangan">
                    <option value="">Semua ruangan</option>
                    <?php foreach ($rooms as $id => $name): ?>
                        <option value="<?= (int) $id ?>"<?= (int) $roomId === (int) $id ? ' selected' : '' ?>><?= Html::encode($name) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <button type="button" class="app-btn app-btn-primary" data-modal-url="<?= $createUrl ?>">
                <i class="mdi mdi-plus" aria-hidden="true"></i> Sesi Infus Baru
            </button>
        </div>
    </div>

    <div class="app-alarm-banner js-alarm-banner" role="alert">
        <i class="mdi mdi-bell-ring" aria-hidden="true"></i>
        <span class="js-banner-text"></span>
        <button type="button" class="app-btn app-btn-sm app-btn-danger js-ack-all"><i class="mdi mdi-bell-off-outline" aria-hidden="true"></i> Akui Semua</button>
    </div>

    <div class="app-toolbar">
        <label class="app-search">
            <i class="mdi mdi-magnify" aria-hidden="true"></i>
            <span class="visually-hidden">Cari bed atau ruangan</span>
            <input type="search" class="js-search" placeholder="Cari kode bed / ruangan…" autocomplete="off">
        </label>

        <div class="app-segmented js-filter" role="group" aria-label="Filter status">
            <button type="button" class="is-active" data-filter="all" aria-pressed="true">Semua</button>
            <button type="button" data-filter="safe" aria-pressed="false"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i>Aman</button>
            <button type="button" data-filter="warning" aria-pressed="false"><i class="mdi mdi-clock-alert-outline" aria-hidden="true"></i>Segera habis</button>
            <button type="button" data-filter="critical" aria-pressed="false"><i class="mdi mdi-bell-ring-outline" aria-hidden="true"></i>Kritis</button>
        </div>

        <div class="app-toolbar-right">
            <div class="app-segmented js-view" role="group" aria-label="Mode tampilan">
                <button type="button" class="is-active" data-view="grid" aria-pressed="true" title="Kartu"><i class="mdi mdi-view-grid-outline" aria-hidden="true"></i><span class="visually-hidden">Kartu</span></button>
                <button type="button" data-view="table" aria-pressed="false" title="Tabel"><i class="mdi mdi-table" aria-hidden="true"></i><span class="visually-hidden">Tabel</span></button>
            </div>
        </div>
    </div>

    <div class="app-summary js-summary"></div>

    <div class="app-view-grid">
        <div class="app-grid js-grid"></div>
    </div>

    <div class="app-view-table app-surface">
        <div class="app-table-wrap">
            <table class="app-table">
                <thead>
                <tr>
                    <th>Bed</th>
                    <th>Ruangan</th>
                    <th>Sisa</th>
                    <th>Status</th>
                    <th>Faktor tetes</th>
                    <th>Volume (mL)</th>
                    <th>TPM</th>
                    <th>Mulai</th>
                    <th>Perkiraan habis</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="js-tbody"></tbody>
            </table>
        </div>
    </div>

    <div class="app-empty js-empty" style="display:none">
        <i class="mdi mdi-iv-bag" aria-hidden="true"></i>
        <p>Belum ada sesi infus aktif<?= $roomName ? ' di ' . Html::encode($roomName) : '' ?>.</p>
        <button type="button" class="app-btn app-btn-primary" data-modal-url="<?= $createUrl ?>">
            <i class="mdi mdi-plus" aria-hidden="true"></i> Mulai sesi infus
        </button>
    </div>

    <div class="app-empty js-no-match" style="display:none">
        <i class="mdi mdi-filter-remove-outline" aria-hidden="true"></i>
        <p>Tidak ada sesi yang cocok dengan pencarian / filter.</p>
    </div>

    <section class="app-idle">
        <div class="app-idle-title"><i class="mdi mdi-bed-empty" aria-hidden="true"></i> Bed tanpa infus aktif</div>
        <div class="app-idle-list js-idle"></div>
    </section>

</div>
