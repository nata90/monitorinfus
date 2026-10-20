<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Masuk';
?>
<?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'options' => ['class' => 'app-form app-stack'],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{error}",
        'errorOptions' => ['class' => 'invalid-feedback'],
    ],
]) ?>

    <?= $form->field($model, 'username')->textInput([
        'autofocus' => true,
        'autocomplete' => 'username',
        'placeholder' => 'mis. petugas',
    ]) ?>

    <?= $form->field($model, 'password')->passwordInput([
        'autocomplete' => 'current-password',
        'placeholder' => '••••••••',
    ]) ?>

    <?= $form->field($model, 'rememberMe')->checkbox() ?>

    <?= Html::submitButton('<i class="mdi mdi-login" aria-hidden="true"></i> Masuk', [
        'class' => 'app-btn app-btn-primary',
        'name' => 'login-button',
    ]) ?>

<?php ActiveForm::end() ?>
