<?php

/** @var yii\web\View $this */
/** @var app\models\InfusionSession $model */
/** @var array $beds opsi bed dikelompokkan per ruangan: [namaRuangan => [id => kode]] */
/** @var int|null $roomId filter ruangan aktif (dari dashboard) */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$isAjax = Yii::$app->request->isAjax;
$roomId = $roomId ?? null;
$title = $model->isNewRecord ? 'Sesi Infus Baru' : 'Ubah Sesi Infus · ' . $model->bed->code;
$action = $model->isNewRecord
    ? ['infusion/create', 'room_id' => $roomId]
    : ['infusion/update', 'id' => $model->id];
$hasBeds = !empty($beds);
$roomNames = array_keys($beds);
$singleRoom = count($roomNames) === 1;
// Bila hanya satu ruangan, tidak perlu optgroup.
$bedOptions = $singleRoom ? reset($beds) : $beds;
$formId = 'infusion-form';

// Tampilkan angka tanpa nol desimal berlebih (DB menyimpan 20.00 -> input 20)
$num = static function ($v) {
    if ($v === null || $v === '') {
        return $v;
    }
    $s = (string) $v;
    return strpos($s, '.') !== false ? rtrim(rtrim($s, '0'), '.') : $s;
};
?>
<?php if ($isAjax): ?>
<div class="modal-header">
    <h2 class="modal-title"><i class="mdi mdi-iv-bag" aria-hidden="true"></i><?= Html::encode($title) ?></h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
<?php endif ?>

<?php $form = ActiveForm::begin([
    'id' => $formId,
    'action' => Url::to($action),
    'enableClientValidation' => true,
    'options' => ['class' => 'app-form app-stack', 'data-ajax-form' => $isAjax ? '1' : null, 'autocomplete' => 'off', 'novalidate' => true],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{hint}\n{error}",
        'errorOptions' => ['class' => 'invalid-feedback'],
        'hintOptions' => ['class' => 'form-text'],
    ],
]) ?>

    <?php if ($model->isNewRecord): ?>
        <?= $form->field($model, 'bed_id')->dropDownList($bedOptions, [
            'prompt' => $hasBeds ? '— Pilih bed —' : 'Tidak ada bed tersedia',
            'disabled' => !$hasBeds,
            'autofocus' => true,
        ])->label($singleRoom && $hasBeds ? 'Bed · ' . $roomNames[0] : 'Bed')
          ->hint($hasBeds
              ? ($roomId ? null : ($singleRoom ? null : 'Dikelompokkan per ruangan.'))
              : ($roomId
                  ? 'Semua bed di ruangan ini sedang memiliki sesi infus aktif.'
                  : 'Semua bed aktif sedang memiliki sesi infus. Selesaikan sesi lain atau tambahkan bed baru.')) ?>
    <?php else: ?>
        <?= $form->field($model, 'bed_id')->dropDownList($bedOptions)
            ->label($singleRoom ? 'Bed · ' . $roomNames[0] : 'Bed') ?>
    <?php endif ?>

    <?php
    // Tiga parameter perhitungan: label satu baris, satuan sebagai sufiks di dalam input.
    $numericField = static function ($attribute, $label, $unit, $key) use ($form, $model, $num) {
        return $form->field($model, $attribute, [
            'template' => "{label}\n<div class=\"input-group app-input-group\">{input}<span class=\"input-group-text\">" . Html::encode($unit) . "</span></div>\n{error}",
        ])->label($label)->textInput([
            'type' => 'number', 'step' => 'any', 'min' => '0.01', 'inputmode' => 'decimal',
            'class' => 'form-control js-calc', 'data-key' => $key, 'value' => $num($model->$attribute),
            'placeholder' => '0',
        ]);
    };
    ?>
    <div class="app-form-grid app-form-grid-3 app-form-params">
        <?= $numericField('drop_factor', 'Faktor tetes', 'tts/mL', 'drop') ?>
        <?= $numericField('volume_ml', 'Volume', 'mL', 'volume') ?>
        <?= $numericField('tpm', 'TPM', 'tts/mnt', 'tpm') ?>
    </div>

    <?= $form->field($model, 'started_at_input')->textInput([
        'type' => 'datetime-local', 'class' => 'form-control js-calc', 'data-key' => 'start',
    ])->hint('Zona waktu ' . Yii::$app->timeZone) ?>

    <div class="app-preview" aria-live="polite">
        <div>
            <div class="app-preview-label">Durasi</div>
            <div class="app-preview-value num"><span class="js-preview-duration">–</span> <small>menit</small></div>
        </div>
        <div>
            <div class="app-preview-label">Perkiraan habis</div>
            <div class="app-preview-value num js-preview-end">–</div>
        </div>
        <div class="app-preview-formula">Durasi = faktor tetes × volume ÷ TPM</div>
    </div>

    <?= $form->field($model, 'notes')->textarea(['rows' => 2, 'placeholder' => 'Opsional, mis. jenis cairan'])->label('Catatan (opsional)') ?>

    <div class="app-form-actions">
        <?php if ($isAjax): ?>
            <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Batal</button>
        <?php else: ?>
            <?= Html::a('Batal', ['dashboard/index'], ['class' => 'app-btn app-btn-secondary']) ?>
        <?php endif ?>
        <?= Html::submitButton(
            '<i class="mdi mdi-content-save-outline" aria-hidden="true"></i> ' . ($model->isNewRecord ? 'Mulai Infus' : 'Simpan Perubahan'),
            ['class' => 'app-btn app-btn-primary', 'disabled' => $model->isNewRecord && !$hasBeds]
        ) ?>
    </div>

<?php ActiveForm::end() ?>

<?php if ($isAjax): ?>
</div>
<?php endif ?>

<?php
$js = <<<JS
(function ($) {
  var \$form = $('#$formId');
  function val(key) { return parseFloat(\$form.find('[data-key="' + key + '"]').val()); }
  function fmt(n) { return n.toLocaleString('id-ID', { maximumFractionDigits: 1 }); }
  function preview() {
    var d = val('drop'), v = val('volume'), t = val('tpm');
    var start = \$form.find('[data-key="start"]').val();
    var \$dur = \$form.find('.js-preview-duration'), \$end = \$form.find('.js-preview-end');
    if (!(d > 0 && v > 0 && t > 0)) { \$dur.text('–'); \$end.text('–'); return; }
    var minutes = d * v / t;
    \$dur.text(fmt(minutes));
    var startMs = start ? Date.parse(start) : Date.now();
    if (isNaN(startMs)) { \$end.text('–'); return; }
    var end = new Date(startMs + Math.round(minutes * 60) * 1000);
    \$end.text(end.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }));
  }
  \$form.on('input change', '.js-calc', preview);
  preview();
})(jQuery);
JS;
$this->registerJs($js);
?>
