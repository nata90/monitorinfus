<?php

/** @var yii\web\View $this */
/** @var app\models\Room $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
?>
<?php $form = ActiveForm::begin([
    'options' => ['class' => 'app-form app-stack'],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{hint}\n{error}",
        'errorOptions' => ['class' => 'invalid-feedback'],
        'hintOptions' => ['class' => 'form-text'],
    ],
]) ?>

    <div class="app-form-grid app-form-grid-2">
        <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'autofocus' => true, 'placeholder' => 'mis. MEL', 'style' => 'text-transform:uppercase'])->hint('Singkatan unik, huruf besar/angka.') ?>
        <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'mis. Ruang Melati']) ?>
    </div>

    <?= $form->field($model, 'description')->textarea(['rows' => 2, 'placeholder' => 'Opsional, mis. lantai / unit']) ?>

    <?= $form->field($model, 'is_active')->checkbox() ?>

    <div class="app-form-actions">
        <?= Html::a('Batal', ['room/index'], ['class' => 'app-btn app-btn-secondary']) ?>
        <?= Html::submitButton('<i class="mdi mdi-content-save-outline" aria-hidden="true"></i> Simpan', ['class' => 'app-btn app-btn-primary']) ?>
    </div>

<?php ActiveForm::end() ?>
