<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link audit entries for child entities to their parent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs ADD parent_entity_type VARCHAR(120) DEFAULT NULL, ADD parent_entity_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_audit_parent_entity ON audit_logs (parent_entity_type, parent_entity_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_audit_parent_entity');
        $this->addSql('ALTER TABLE audit_logs DROP parent_entity_type, DROP parent_entity_id');
    }
}
