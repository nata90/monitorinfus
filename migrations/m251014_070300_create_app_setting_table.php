<?php

use yii\db\Migration;

/**
 * Tabel konfigurasi aplikasi (ambang peringatan & alarm).
 */
class m251014_070300_create_app_setting_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%app_setting}}', [
            'id' => $this->primaryKey(),
            'setting_key' => $this->string(64)->notNull()->unique(),
            'setting_value' => $this->string(255)->notNull(),
            'description' => $this->string(255)->null(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $now = date('Y-m-d H:i:s');
        $this->batchInsert('{{%app_setting}}', ['setting_key', 'setting_value', 'description', 'updated_at'], [
            ['infusion_warning_minutes', '30', 'Sisa menit untuk status "Segera habis"', $now],
            ['infusion_alarm_minutes', '2', 'Sisa menit untuk memicu alarm', $now],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%app_setting}}');
    }
}
