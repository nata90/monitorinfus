<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\helpers\Html;

AppAsset::register($this);

$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerMetaTag(['name' => 'color-scheme', 'content' => 'light dark']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/svg+xml', 'href' => Yii::getAlias('@web/favicon.svg')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title . ' · ' . Yii::$app->name) ?></title>
</head>
<body class="app-body app-body-auth">
<?php $this->beginBody() ?>

<main class="app-auth">
    <div class="app-auth-card">
        <div class="app-auth-brand">
            <span class="app-brand-mark app-brand-mark-lg"><i class="mdi mdi-iv-bag" aria-hidden="true"></i></span>
            <div>
                <div class="app-auth-title"><?= Html::encode(Yii::$app->name) ?></div>
                <div class="app-auth-sub">Penghitung tetesan &amp; countdown infus</div>
            </div>
        </div>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
    <p class="app-auth-footnote">Alat bantu pemantauan — tidak menggantikan observasi langsung.</p>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
