<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\models\User;
use app\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);

$identity = Yii::$app->user->identity;
$isAdmin = $identity instanceof User && $identity->getIsAdmin();
$route = Yii::$app->controller->id;
$appName = Yii::$app->name;

$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerMetaTag(['name' => 'color-scheme', 'content' => 'light dark']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/svg+xml', 'href' => Yii::getAlias('@web/favicon.svg')]);

$navItems = [
    ['label' => 'Dashboard', 'icon' => 'mdi-view-dashboard-outline', 'url' => ['dashboard/index'], 'active' => $route === 'dashboard'],
    ['label' => 'Ruangan', 'icon' => 'mdi-door-open', 'url' => ['room/index'], 'active' => $route === 'room'],
    ['label' => 'Bed', 'icon' => 'mdi-bed-outline', 'url' => ['bed/index'], 'active' => $route === 'bed'],
];
if ($isAdmin) {
    $navItems[] = ['label' => 'Pengguna', 'icon' => 'mdi-account-multiple-outline', 'url' => ['user/index'], 'active' => $route === 'user'];
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title ? $this->title . ' · ' . $appName : $appName) ?></title>
</head>
<body class="app-body">
<?php $this->beginBody() ?>

<header class="app-header">
    <div class="app-container app-header-inner">
        <a class="app-brand" href="<?= Url::to(['dashboard/index']) ?>">
            <span class="app-brand-mark"><i class="mdi mdi-iv-bag" aria-hidden="true"></i></span>
            <span class="app-brand-text"><?= Html::encode($appName) ?></span>
        </a>

        <nav class="app-nav" aria-label="Navigasi utama">
            <?php foreach ($navItems as $item): ?>
                <a class="app-nav-link<?= $item['active'] ? ' is-active' : '' ?>" href="<?= Url::to($item['url']) ?>"<?= $item['active'] ? ' aria-current="page"' : '' ?>>
                    <i class="mdi <?= $item['icon'] ?>" aria-hidden="true"></i>
                    <span><?= Html::encode($item['label']) ?></span>
                </a>
            <?php endforeach ?>
        </nav>

        <div class="app-header-actions">
            <?php if ($route === 'dashboard'): ?>
                <button type="button" class="app-btn app-btn-accent app-sound-toggle" id="soundToggle" aria-pressed="false" title="Aktifkan suara alarm">
                    <i class="mdi mdi-volume-off" aria-hidden="true"></i>
                    <span class="app-sound-label">Aktifkan Suara</span>
                </button>
            <?php endif ?>

            <div class="dropdown app-user-menu">
                <button class="app-user-button" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="app-avatar"><i class="mdi mdi-account" aria-hidden="true"></i></span>
                    <span class="app-user-name"><?= Html::encode($identity ? ($identity->full_name ?: $identity->username) : '') ?></span>
                    <i class="mdi mdi-chevron-down" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end app-dropdown">
                    <li class="app-dropdown-header">
                        <div class="app-dropdown-title"><?= Html::encode($identity->username ?? '') ?></div>
                        <div class="app-dropdown-sub"><?= Html::encode($identity ? $identity->getRoleLabel() : '') ?></div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <?= Html::beginForm(['site/logout'], 'post', ['class' => 'm-0']) ?>
                        <button type="submit" class="dropdown-item"><i class="mdi mdi-logout" aria-hidden="true"></i> Keluar</button>
                        <?= Html::endForm() ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<main class="app-main" id="main">
    <div class="app-container">
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer class="app-footer">
    <div class="app-container">
        <i class="mdi mdi-information-outline" aria-hidden="true"></i>
        Estimasi berdasarkan formula faktor tetes × volume ÷ TPM. Alat bantu pemantauan — tidak menggantikan observasi langsung dan penilaian klinis.
    </div>
</footer>

<div class="modal fade app-modal" id="appModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"></div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
