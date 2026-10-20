<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Master ruangan.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Bed[] $beds
 */
class Room extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%room}}';
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
            [['code', 'name'], 'required'],
            [['code', 'name'], 'trim'],
            [['code'], 'string', 'max' => 16],
            [['code'], 'filter', 'filter' => 'strtoupper'],
            // Case-insensitive agar validasi client tidak menolak huruf kecil; disimpan huruf besar oleh filter di atas.
            [['code'], 'match', 'pattern' => '/^[A-Za-z0-9-]+$/', 'message' => 'Hanya huruf, angka, dan strip.'],
            [['code'], 'unique'],
            [['name'], 'string', 'max' => 128],
            [['name'], 'unique'],
            [['description'], 'string'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'code' => 'Kode Ruangan',
            'name' => 'Nama Ruangan',
            'description' => 'Keterangan',
            'is_active' => 'Aktif',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diubah',
        ];
    }

    public function getBeds(): ActiveQuery
    {
        return $this->hasMany(Bed::class, ['room_id' => 'id'])->orderBy(['code' => SORT_ASC]);
    }

    public function getActiveBedCount(): int
    {
        return (int) $this->getBeds()->andWhere(['is_active' => 1])->count();
    }

    public function getActiveSessionCount(): int
    {
        return (int) InfusionSession::find()
            ->alias('s')
            ->innerJoin(['b' => Bed::tableName()], 'b.id = s.bed_id')
            ->where(['b.room_id' => $this->id, 's.status' => InfusionSession::STATUS_ACTIVE])
            ->count();
    }

    /**
     * Daftar ruangan aktif untuk dropdown: [id => name].
     */
    public static function options(bool $onlyActive = true): array
    {
        $q = static::find()->orderBy(['name' => SORT_ASC]);
        if ($onlyActive) {
            $q->where(['is_active' => 1]);
        }

        return $q->select(['name', 'id'])->indexBy('id')->column();
    }
}
