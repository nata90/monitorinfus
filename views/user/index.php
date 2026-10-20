<?php

/** @var yii\web\View $this */
/** @var app\models\User[] $users */

use app\models\User;
use yii\helpers\Html;

$this->title = 'Pengguna';
$fmt = Yii::$app->formatter;
?>
<div class="app-page-header">
    <div>
        <h1 class="app-page-title">Pengguna</h1>
        <p class="app-page-sub"><?= count($users) ?> akun</p>
    </div>
    <div class="app-page-actions">
        <?= Html::a('<i class="mdi mdi-account-plus-outline" aria-hidden="true"></i> Tambah Pengguna', ['user/create'], ['class' => 'app-btn app-btn-primary']) ?>
    </div>
</div>

<div class="app-surface">
    <div class="app-table-wrap">
        <table class="app-table">
            <thead>
            <tr>
                <th>Username</th>
                <th>Nama</th>
                <th>Peran</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="cell-strong"><?= Html::encode($u->username) ?><?= (int) $u->id === (int) Yii::$app->user->id ? ' <span class="app-badge app-badge-neutral">Anda</span>' : '' ?></td>
                    <td><?= Html::encode($u->full_name) ?></td>
                    <td><span class="app-badge <?= $u->getIsAdmin() ? 'app-badge-primary' : 'app-badge-neutral' ?>"><?= Html::encode($u->getRoleLabel()) ?></span></td>
                    <td>
                        <?php if ($u->status == User::STATUS_ACTIVE): ?>
                            <span class="app-badge app-badge-safe"><i class="mdi mdi-check" aria-hidden="true"></i>Aktif</span>
                        <?php else: ?>
                            <span class="app-badge app-badge-neutral"><i class="mdi mdi-minus" aria-hidden="true"></i>Nonaktif</span>
                        <?php endif ?>
                    </td>
                    <td class="app-muted num"><?= $fmt->asDatetime($u->created_at) ?></td>
                    <td class="cell-actions">
                        <?= Html::a('<i class="mdi mdi-pencil-outline" aria-hidden="true"></i>', ['user/update', 'id' => $u->id], ['class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon', 'title' => 'Ubah', 'aria-label' => 'Ubah']) ?>
                        <?php if ((int) $u->id !== (int) Yii::$app->user->id): ?>
                            <?= Html::a(
                                '<i class="mdi ' . ($u->status ? 'mdi-account-off-outline' : 'mdi-account-check-outline') . '" aria-hidden="true"></i>',
                                ['user/toggle', 'id' => $u->id],
                                [
                                    'class' => 'app-btn app-btn-sm app-btn-ghost app-btn-icon',
                                    'title' => $u->status ? 'Nonaktifkan' : 'Aktifkan',
                                    'aria-label' => $u->status ? 'Nonaktifkan' : 'Aktifkan',
                                    'data-method' => 'post',
                                    'data-confirm' => ($u->status ? 'Nonaktifkan' : 'Aktifkan') . ' akun ' . $u->username . '?',
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
