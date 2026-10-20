<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $code
 * @property int $room_id
 * @property string|null $description
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Room $room
 * @property InfusionSession|null $activeSession
 * @property InfusionSession[] $sessions
 * @property string $roomName
 */
class Bed extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%bed}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['code', 'room_id'], 'required'],
            [['code'], 'trim'],
            [['code'], 'string', 'max' => 32],
            [['code'], 'unique'],
            [['room_id'], 'integer'],
            [['room_id'], 'exist', 'targetClass' => Room::class, 'targetAttribute' => 'id', 'message' => 'Ruangan tidak ditemukan.'],
            [['description'], 'string'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'code' => 'Kode Bed',
            'room_id' => 'Ruangan',
            'roomName' => 'Ruangan',
            'description' => 'Keterangan',
            'is_active' => 'Aktif',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diubah',
        ];
    }

    public function getRoom(): ActiveQuery
    {
        return $this->hasOne(Room::class, ['id' => 'room_id']);
    }

    public function getSessions(): ActiveQuery
    {
        return $this->hasMany(InfusionSession::class, ['bed_id' => 'id'])->orderBy(['started_at' => SORT_DESC]);
    }

    public function getActiveSession(): ActiveQuery
    {
        return $this->hasOne(InfusionSession::class, ['bed_id' => 'id'])
            ->andOnCondition(['status' => InfusionSession::STATUS_ACTIVE]);
    }

    public function getRoomName(): string
    {
        return $this->room ? $this->room->name : '';
    }

    public function getLabel(): string
    {
        return $this->code . ' · ' . $this->getRoomName();
    }

    /**
     * Bed aktif (di ruangan aktif) yang belum memiliki sesi infus aktif.
     *
     * @param int|null $includeBedId bed yang tetap disertakan walau punya sesi aktif (saat edit)
     * @param int|null $roomId batasi ke satu ruangan
     * @return Bed[]
     */
    public static function findAvailable(?int $includeBedId = null, ?int $roomId = null): array
    {
        $activeBedIds = InfusionSession::find()
            ->select('bed_id')
            ->where(['status' => InfusionSession::STATUS_ACTIVE])
            ->column();

        if ($includeBedId !== null) {
            $activeBedIds = array_diff($activeBedIds, [$includeBedId]);
        }

        $query = static::find()
            ->alias('b')
            ->with('room')
            ->innerJoin(['r' => Room::tableName()], 'r.id = b.room_id')
            ->where(['b.is_active' => 1, 'r.is_active' => 1])
            ->andFilterWhere(['not in', 'b.id', $activeBedIds])
            ->orderBy(['r.name' => SORT_ASC, 'b.code' => SORT_ASC]);

        if ($roomId !== null) {
            $query->andWhere(['b.room_id' => $roomId]);
        }

        return $query->all();
    }

    /**
     * Opsi dropdown bed dikelompokkan per ruangan (untuk optgroup).
     *
     * @param Bed[] $beds
     * @return array<string, array<int, string>>
     */
    public static function groupedOptions(array $beds): array
    {
        $out = [];
        foreach ($beds as $bed) {
            $out[$bed->getRoomName()][$bed->id] = $bed->code;
        }

        return $out;
    }
}
