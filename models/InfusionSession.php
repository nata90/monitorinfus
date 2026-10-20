<?php

namespace app\models;

use app\services\InfusionCalculator;
use DateTimeImmutable;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $bed_id
 * @property string $drop_factor
 * @property string $volume_ml
 * @property string $tpm
 * @property string $duration_minutes
 * @property string $started_at
 * @property string $estimated_end_at
 * @property string $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $completed_at
 *
 * @property Bed $bed
 * @property User|null $creator
 * @property User|null $updater
 */
class InfusionSession extends ActiveRecord
{
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_STOPPED = 'stopped';

    // Status tampilan (turunan dari sisa waktu)
    const DISPLAY_SAFE = 'safe';
    const DISPLAY_WARNING = 'warning';
    const DISPLAY_ALARM = 'alarm';
    const DISPLAY_EMPTY = 'empty';
    const DISPLAY_STOPPED = 'stopped';
    const DISPLAY_COMPLETED = 'completed';

    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const INPUT_FORMAT = 'Y-m-d\TH:i';

    /** @var string|null nilai input form (datetime-local) */
    public $started_at_input;

    public static function tableName()
    {
        return '{{%infusion_session}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::class,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['bed_id', 'drop_factor', 'volume_ml', 'tpm', 'started_at_input'], 'required'],
            [['bed_id'], 'integer'],
            [['bed_id'], 'exist', 'targetClass' => Bed::class, 'targetAttribute' => 'id',
                'filter' => ['is_active' => 1], 'message' => 'Bed tidak ditemukan atau nonaktif.'],
            [['drop_factor', 'tpm'], 'number', 'min' => 0.01, 'max' => 999999.99,
                'tooSmall' => '{attribute} harus lebih besar dari nol.'],
            [['volume_ml'], 'number', 'min' => 0.01, 'max' => 99999999.99,
                'tooSmall' => '{attribute} harus lebih besar dari nol.'],
            [['started_at_input'], 'datetime', 'format' => 'php:' . self::INPUT_FORMAT,
                'timestampAttribute' => 'started_at', 'timestampAttributeFormat' => 'php:' . self::DATETIME_FORMAT,
                'timestampAttributeTimeZone' => Yii::$app->timeZone, 'timeZone' => Yii::$app->timeZone],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['notes'], 'string', 'max' => 2000],
            [['notes'], 'default', 'value' => null],
            [['bed_id'], 'validateSingleActiveSession'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'bed_id' => 'Bed',
            'drop_factor' => 'Faktor tetes (tetes/mL)',
            'volume_ml' => 'Volume (mL)',
            'tpm' => 'TPM (tetes/menit)',
            'duration_minutes' => 'Durasi (menit)',
            'started_at' => 'Waktu mulai',
            'started_at_input' => 'Waktu mulai',
            'estimated_end_at' => 'Perkiraan habis',
            'status' => 'Status',
            'notes' => 'Catatan',
            'created_by' => 'Dibuat oleh',
            'updated_by' => 'Diubah oleh',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diubah',
            'completed_at' => 'Diselesaikan',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_STOPPED => 'Dihentikan',
        ];
    }

    public static function displayLabels(): array
    {
        return [
            self::DISPLAY_SAFE => 'Aman',
            self::DISPLAY_WARNING => 'Segera habis',
            self::DISPLAY_ALARM => 'Alarm',
            self::DISPLAY_EMPTY => 'Habis',
            self::DISPLAY_STOPPED => 'Dihentikan',
            self::DISPLAY_COMPLETED => 'Selesai',
        ];
    }

    /**
     * Satu bed hanya boleh memiliki satu sesi aktif.
     */
    public function validateSingleActiveSession($attribute, $params)
    {
        if ($this->status !== self::STATUS_ACTIVE || $this->hasErrors($attribute)) {
            return;
        }

        $query = static::find()->where(['bed_id' => $this->bed_id, 'status' => self::STATUS_ACTIVE]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Bed ini sudah memiliki sesi infus aktif. Selesaikan sesi tersebut terlebih dahulu.');
        }
    }

    public function afterFind()
    {
        parent::afterFind();
        if ($this->started_at) {
            $this->started_at_input = (new DateTimeImmutable($this->started_at))->format(self::INPUT_FORMAT);
        }
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord && empty($this->started_at_input)) {
            $this->started_at_input = (new DateTimeImmutable())->format(self::INPUT_FORMAT);
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $calc = new InfusionCalculator();
        $duration = $calc->durationMinutes($this->drop_factor, $this->volume_ml, $this->tpm);
        $this->duration_minutes = number_format($duration, 4, '.', '');
        $this->estimated_end_at = $calc
            ->estimatedEnd(new DateTimeImmutable($this->started_at), $duration)
            ->format(self::DATETIME_FORMAT);

        if ($this->status !== self::STATUS_ACTIVE && empty($this->completed_at)) {
            $this->completed_at = date(self::DATETIME_FORMAT);
        }
        if ($this->status === self::STATUS_ACTIVE) {
            $this->completed_at = null;
        }

        return true;
    }

    // ---- Relasi ----

    public function getBed(): ActiveQuery
    {
        return $this->hasOne(Bed::class, ['id' => 'bed_id']);
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getUpdater(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    // ---- Turunan ----

    public function getIsActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getEstimatedEnd(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->estimated_end_at);
    }

    public function getRemainingMinutes(?\DateTimeInterface $now = null): int
    {
        return (new InfusionCalculator())->remainingMinutes($this->getEstimatedEnd(), $now ?: new DateTimeImmutable());
    }

    public function getRemainingSeconds(?\DateTimeInterface $now = null): int
    {
        return (new InfusionCalculator())->remainingSeconds($this->getEstimatedEnd(), $now ?: new DateTimeImmutable());
    }

    public function getDisplayStatus(?\DateTimeInterface $now = null): string
    {
        if ($this->status === self::STATUS_STOPPED) {
            return self::DISPLAY_STOPPED;
        }
        if ($this->status === self::STATUS_COMPLETED) {
            return self::DISPLAY_COMPLETED;
        }

        $remaining = $this->getRemainingMinutes($now);
        if ($remaining <= 0) {
            return self::DISPLAY_EMPTY;
        }
        if ($remaining <= AppSetting::alarmMinutes()) {
            return self::DISPLAY_ALARM;
        }
        if ($remaining <= AppSetting::warningMinutes()) {
            return self::DISPLAY_WARNING;
        }

        return self::DISPLAY_SAFE;
    }

    public function getDisplayLabel(?\DateTimeInterface $now = null): string
    {
        return self::displayLabels()[$this->getDisplayStatus($now)];
    }

    public function markCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = date(self::DATETIME_FORMAT);

        return $this->save(false);
    }

    public function markStopped(): bool
    {
        $this->status = self::STATUS_STOPPED;
        $this->completed_at = date(self::DATETIME_FORMAT);

        return $this->save(false);
    }

    /**
     * Representasi untuk endpoint JSON dashboard.
     */
    public function toDashboardArray(?\DateTimeInterface $now = null): array
    {
        $now = $now ?: new DateTimeImmutable();

        return [
            'id' => (int) $this->id,
            'bed_id' => (int) $this->bed_id,
            'bed_code' => $this->bed->code,
            'room_id' => (int) $this->bed->room_id,
            'room_name' => $this->bed->getRoomName(),
            'drop_factor' => (float) $this->drop_factor,
            'volume_ml' => (float) $this->volume_ml,
            'tpm' => (float) $this->tpm,
            'duration_minutes' => (float) $this->duration_minutes,
            'started_at' => (new DateTimeImmutable($this->started_at))->format(DATE_ATOM),
            'estimated_end_at' => $this->getEstimatedEnd()->format(DATE_ATOM),
            'remaining_minutes' => $this->getRemainingMinutes($now),
            'remaining_seconds' => $this->getRemainingSeconds($now),
            'status' => $this->status,
            'display_status' => $this->getDisplayStatus($now),
            'notes' => $this->notes,
        ];
    }
}
