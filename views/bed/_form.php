<?php

/** @var yii\web\View $this */
/** @var app\models\Bed $model */
/** @var array $rooms [id => nama ruangan] */

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
        <?= $form->field($model, 'room_id')->dropDownList($rooms, [
            'prompt' => $rooms ? '— Pilih ruangan —' : 'Belum ada ruangan aktif',
            'disabled' => !$rooms,
            'autofocus' => true,
        ])->hint($rooms ? null : Html::a('Tambahkan ruangan terlebih dahulu', ['room/create'])) ?>
        <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'placeholder' => 'mis. MEL-01'])->hint('Unik, singkat, mudah dibaca dari jauh.') ?>
    </div>

    <?= $form->field($model, 'description')->textarea(['rows' => 2, 'placeholder' => 'Opsional']) ?>

    <?= $form->field($model, 'is_active')->checkbox() ?>

    <div class="app-form-actions">
        <?= Html::a('Batal', ['bed/index'], ['class' => 'app-btn app-btn-secondary']) ?>
        <?= Html::submitButton('<i class="mdi mdi-content-save-outline" aria-hidden="true"></i> Simpan', ['class' => 'app-btn app-btn-primary', 'disabled' => !$rooms]) ?>
    </div>

<?php ActiveForm::end() ?>
