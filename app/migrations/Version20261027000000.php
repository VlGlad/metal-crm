<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove redundant personal and request data from audit logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs DROP user_email, DROP user_name, DROP ip_address, DROP user_agent');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs ADD user_email VARCHAR(180) DEFAULT NULL, ADD user_name VARCHAR(255) DEFAULT NULL, ADD ip_address VARCHAR(45) DEFAULT NULL, ADD user_agent TEXT DEFAULT NULL');
    }
}
