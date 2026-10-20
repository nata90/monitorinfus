<?php

use yii\db\Migration;

/**
 * Tabel bed pasien.
 */
class m251014_070100_create_bed_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%bed}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'room_name' => $this->string(128)->notNull(),
            'description' => $this->text()->null(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx_bed_is_active', '{{%bed}}', 'is_active');
        $this->createIndex('idx_bed_room_name', '{{%bed}}', 'room_name');
    }

    public function safeDown()
    {
        $this->dropTable('{{%bed}}');
    }
}
