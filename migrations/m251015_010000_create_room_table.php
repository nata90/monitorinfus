<?php

use yii\db\Migration;

/**
 * Master ruangan. Bed dipindahkan dari kolom teks `room_name` ke relasi `room_id`.
 */
class m251015_010000_create_room_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%room}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(16)->notNull()->unique(),
            'name' => $this->string(128)->notNull()->unique(),
            'description' => $this->text()->null(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);
        $this->createIndex('idx_room_is_active', '{{%room}}', 'is_active');

        // Migrasi data: setiap nama ruangan unik pada bed menjadi satu baris room.
        $now = date('Y-m-d H:i:s');
        $names = (new \yii\db\Query())->select('room_name')->distinct()->from('{{%bed}}')->orderBy('room_name')->column($this->db);
        $usedCodes = [];
        foreach ($names as $name) {
            $code = $this->makeCode($name, $usedCodes);
            $usedCodes[] = $code;
            $this->insert('{{%room}}', [
                'code' => $code, 'name' => $name, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $this->addColumn('{{%bed}}', 'room_id', $this->integer()->null()->after('code'));
        $this->execute('UPDATE {{%bed}} b JOIN {{%room}} r ON r.name = b.room_name SET b.room_id = r.id');
        $this->alterColumn('{{%bed}}', 'room_id', $this->integer()->notNull());
        $this->createIndex('idx_bed_room_id', '{{%bed}}', 'room_id');
        $this->addForeignKey('fk_bed_room', '{{%bed}}', 'room_id', '{{%room}}', 'id', 'RESTRICT', 'CASCADE');

        $this->dropIndex('idx_bed_room_name', '{{%bed}}');
        $this->dropColumn('{{%bed}}', 'room_name');
    }

    public function safeDown()
    {
        $this->addColumn('{{%bed}}', 'room_name', $this->string(128)->notNull()->defaultValue(''));
        $this->execute('UPDATE {{%bed}} b JOIN {{%room}} r ON r.id = b.room_id SET b.room_name = r.name');
        $this->createIndex('idx_bed_room_name', '{{%bed}}', 'room_name');
        $this->dropForeignKey('fk_bed_room', '{{%bed}}');
        $this->dropIndex('idx_bed_room_id', '{{%bed}}');
        $this->dropColumn('{{%bed}}', 'room_id');
        $this->dropTable('{{%room}}');
    }

    private function makeCode(string $name, array $used): string
    {
        $clean = preg_replace('/^ruang(an)?\s+/i', '', trim($name));
        $words = preg_split('/\s+/', $clean) ?: [];
        if (count($words) >= 2) {
            $base = '';
            foreach ($words as $w) {
                $base .= mb_substr($w, 0, 1);
            }
        } else {
            $base = mb_substr($clean, 0, 3);
        }
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $base)) ?: 'R';
        $code = $base;
        $i = 2;
        while (in_array($code, $used, true)) {
            $code = $base . $i++;
        }

        return mb_substr($code, 0, 16);
    }
}
