<?php

use yii\db\Migration;

/**
 * Tabel sesi infus per bed.
 */
class m251014_070200_create_infusion_session_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%infusion_session}}', [
            'id' => $this->primaryKey(),
            'bed_id' => $this->integer()->notNull(),
            'drop_factor' => $this->decimal(8, 2)->notNull()->comment('tetes/mL'),
            'volume_ml' => $this->decimal(10, 2)->notNull()->comment('mL'),
            'tpm' => $this->decimal(8, 2)->notNull()->comment('tetes/menit'),
            'duration_minutes' => $this->decimal(12, 4)->notNull()->comment('faktor tetes x volume / TPM'),
            'started_at' => $this->dateTime()->notNull(),
            'estimated_end_at' => $this->dateTime()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('active'),
            'notes' => $this->text()->null(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'completed_at' => $this->dateTime()->null(),
        ], $tableOptions);

        $this->createIndex('idx_infusion_status', '{{%infusion_session}}', 'status');
        $this->createIndex('idx_infusion_bed_id', '{{%infusion_session}}', 'bed_id');
        $this->createIndex('idx_infusion_estimated_end_at', '{{%infusion_session}}', 'estimated_end_at');
        $this->createIndex('idx_infusion_bed_status', '{{%infusion_session}}', ['bed_id', 'status']);

        $this->addForeignKey('fk_infusion_bed', '{{%infusion_session}}', 'bed_id', '{{%bed}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_infusion_created_by', '{{%infusion_session}}', 'created_by', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_infusion_updated_by', '{{%infusion_session}}', 'updated_by', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_infusion_updated_by', '{{%infusion_session}}');
        $this->dropForeignKey('fk_infusion_created_by', '{{%infusion_session}}');
        $this->dropForeignKey('fk_infusion_bed', '{{%infusion_session}}');
        $this->dropTable('{{%infusion_session}}');
    }
}
