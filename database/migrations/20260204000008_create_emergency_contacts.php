<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmergencyContacts extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('emergency_contacts', ['signed' => false]);
        
        $table
            ->addColumn('student_id', 'integer', ['signed' => false])
            ->addColumn('full_name', 'string', ['limit' => 150])
            ->addColumn('relationship', 'string', ['limit' => 50])
            ->addColumn('phone', 'string', ['limit' => 20])
            ->addColumn('phone_secondary', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('is_primary', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['student_id'])
            ->addForeignKey('student_id', 'students', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
