<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $setting_key
 * @property string $setting_value
 * @property string|null $description
 * @property string $updated_at
 */
class AppSetting extends ActiveRecord
{
    const KEY_WARNING_MINUTES = 'infusion_warning_minutes';
    const KEY_ALARM_MINUTES = 'infusion_alarm_minutes';

    /** @var array<string,string>|null */
    private static $cache;

    public static function tableName()
    {
        return '{{%app_setting}}';
    }

    public function rules()
    {
        return [
            [['setting_key', 'setting_value'], 'required'],
            [['setting_key'], 'string', 'max' => 64],
            [['setting_value', 'description'], 'string', 'max' => 255],
            [['setting_key'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'setting_key' => 'Kunci',
            'setting_value' => 'Nilai',
            'description' => 'Keterangan',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->updated_at = date('Y-m-d H:i:s');
        self::$cache = null;

        return true;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            try {
                self::$cache = static::find()
                    ->select(['setting_value', 'setting_key'])
                    ->indexBy('setting_key')
                    ->column();
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }

        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, string $value, ?string $description = null): bool
    {
        $model = static::findOne(['setting_key' => $key]) ?: new static(['setting_key' => $key]);
        $model->setting_value = $value;
        if ($description !== null) {
            $model->description = $description;
        }

        return $model->save();
    }

    public static function warningMinutes(): int
    {
        return (int) self::get(self::KEY_WARNING_MINUTES, Yii::$app->params['defaultWarningMinutes'] ?? 30);
    }

    public static function alarmMinutes(): int
    {
        return (int) self::get(self::KEY_ALARM_MINUTES, Yii::$app->params['defaultAlarmMinutes'] ?? 2);
    }
}
