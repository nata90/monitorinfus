<?php

use yii\db\Migration;

/**
 * Tabel pengguna aplikasi (admin / petugas).
 */
class m251014_070000_create_user_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(64)->notNull()->unique(),
            'full_name' => $this->string(128)->notNull()->defaultValue(''),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'role' => $this->string(16)->notNull()->defaultValue('petugas'),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx_user_role', '{{%user}}', 'role');
        $this->createIndex('idx_user_status', '{{%user}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}
