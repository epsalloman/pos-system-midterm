<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPosProductsSalesAuthentication extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('password', 'users')) {
            $this->forge->addColumn('users', [
                'password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'username'],
            ]);
            $temporaryHash = password_hash('ChangeMe123!', PASSWORD_DEFAULT);
            $this->db->table('users')->where('password', null)->update(['password' => $temporaryHash]);
            $this->forge->modifyColumn('users', [
                'password' => ['name' => 'password', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            ]);
        }

        if (! $this->db->fieldExists('is_active', 'users')) {
            $this->forge->addColumn('users', [
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'avatar'],
            ]);
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'stock_quantity' => ['type' => 'INT', 'default' => 0],
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('products', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'product_id' => ['type' => 'INT'],
            'customer_id' => ['type' => 'INT', 'null' => true],
            'sold_by' => ['type' => 'INT'],
            'quantity' => ['type' => 'INT'],
            'total_price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('sold_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('sales', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('sales', true);
        $this->forge->dropTable('products', true);
        if ($this->db->fieldExists('is_active', 'users')) $this->forge->dropColumn('users', 'is_active');
        if ($this->db->fieldExists('password', 'users')) $this->forge->dropColumn('users', 'password');
    }
}
