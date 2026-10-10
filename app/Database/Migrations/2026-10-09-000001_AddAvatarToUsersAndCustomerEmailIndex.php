<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAvatarToUsersAndCustomerEmailIndex extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('avatar', 'users')) {
            $this->forge->addColumn('users', [
                'avatar' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'full_name',
                ],
            ]);
        }

        $indexes = $this->db->getIndexData('customers');

        if (! isset($indexes['customers_email_unique'])) {
            $this->db->query(
                'ALTER TABLE `customers` ADD UNIQUE KEY `customers_email_unique` (`email`)'
            );
        }
    }

    public function down()
    {
        $indexes = $this->db->getIndexData('customers');

        if (isset($indexes['customers_email_unique'])) {
            $this->db->query(
                'ALTER TABLE `customers` DROP INDEX `customers_email_unique`'
            );
        }

        if ($this->db->fieldExists('avatar', 'users')) {
            $this->forge->dropColumn('users', 'avatar');
        }
    }
}
