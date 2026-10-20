<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $full_name
 * @property string $password_hash
 * @property string $auth_key
 * @property string $role
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    const ROLE_ADMIN = 'admin';
    const ROLE_PETUGAS = 'petugas';

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /** @var string|null password baru (hanya saat create/update via form) */
    public $password;

    public static function tableName()
    {
        return '{{%user}}';
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
            [['username', 'full_name', 'role'], 'required'],
            [['username'], 'trim'],
            [['username'], 'string', 'min' => 3, 'max' => 64],
            [['username'], 'match', 'pattern' => '/^[a-zA-Z0-9._-]+$/', 'message' => 'Hanya huruf, angka, titik, garis bawah, dan strip.'],
            [['username'], 'unique'],
            [['full_name'], 'string', 'max' => 128],
            [['role'], 'in', 'range' => array_keys(self::roleLabels())],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['password'], 'string', 'min' => 6, 'max' => 72],
            [['password'], 'required', 'on' => 'create'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Username',
            'full_name' => 'Nama Lengkap',
            'password' => 'Password',
            'role' => 'Peran',
            'status' => 'Status',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diubah',
        ];
    }

    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_PETUGAS => 'Petugas',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_INACTIVE => 'Nonaktif',
        ];
    }

    public function getRoleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? $this->role;
    }

    public function getIsAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->password !== null && $this->password !== '') {
            $this->setPassword($this->password);
        }

        if ($insert || empty($this->auth_key)) {
            $this->generateAuthKey();
        }

        return true;
    }

    // ---- IdentityInterface ----

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByUsername(string $username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
}
