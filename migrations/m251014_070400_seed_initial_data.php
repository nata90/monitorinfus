<?php

use yii\db\Migration;

/**
 * Data awal: akun admin & petugas, serta contoh bed.
 * Ganti password akun setelah login pertama.
 */
class m251014_070400_seed_initial_data extends Migration
{
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');
        $security = Yii::$app->security;

        $this->batchInsert('{{%user}}', [
            'username', 'full_name', 'password_hash', 'auth_key', 'role', 'status', 'created_at', 'updated_at',
        ], [
            ['admin', 'Administrator', $security->generatePasswordHash('admin123'), $security->generateRandomString(), 'admin', 1, $now, $now],
            ['petugas', 'Petugas Jaga', $security->generatePasswordHash('petugas123'), $security->generateRandomString(), 'petugas', 1, $now, $now],
        ]);

        $beds = [];
        foreach (['Melati' => 4, 'Anggrek' => 4] as $room => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $beds[] = [strtoupper(substr($room, 0, 3)) . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'Ruang ' . $room, null, 1, $now, $now];
            }
        }
        $this->batchInsert('{{%bed}}', ['code', 'room_name', 'description', 'is_active', 'created_at', 'updated_at'], $beds);
    }

    public function safeDown()
    {
        $this->delete('{{%infusion_session}}');
        $this->delete('{{%bed}}');
        $this->delete('{{%user}}', ['username' => ['admin', 'petugas']]);
    }
}
