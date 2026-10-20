<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */

use app\models\User;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
?>
<?php $form = ActiveForm::begin([
    'options' => ['class' => 'app-form app-stack', 'autocomplete' => 'off'],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{hint}\n{error}",
        'errorOptions' => ['class' => 'invalid-feedback'],
        'hintOptions' => ['class' => 'form-text'],
    ],
]) ?>

    <div class="app-form-grid app-form-grid-2">
        <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'autofocus' => $model->isNewRecord, 'autocomplete' => 'off']) ?>
        <?= $form->field($model, 'full_name')->textInput(['maxlength' => true]) ?>
    </div>

    <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'new-password'])
        ->hint($model->isNewRecord ? 'Minimal 6 karakter.' : 'Kosongkan jika tidak ingin mengubah password.') ?>

    <div class="app-form-grid app-form-grid-2">
        <?= $form->field($model, 'role')->dropDownList(User::roleLabels()) ?>
        <?= $form->field($model, 'status')->dropDownList(User::statusLabels()) ?>
    </div>

    <div class="app-form-actions">
        <?= Html::a('Batal', ['user/index'], ['class' => 'app-btn app-btn-secondary']) ?>
        <?= Html::submitButton('<i class="mdi mdi-content-save-outline" aria-hidden="true"></i> Simpan', ['class' => 'app-btn app-btn-primary']) ?>
    </div>

<?php ActiveForm::end() ?>
