<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds responsible user to production orders.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_orders ADD responsible_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PRODUCTION_ORDERS_RESPONSIBLE ON production_orders (responsible_id)');
        $this->addSql('ALTER TABLE production_orders ADD CONSTRAINT FK_PRODUCTION_ORDERS_RESPONSIBLE FOREIGN KEY (responsible_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_orders DROP CONSTRAINT FK_PRODUCTION_ORDERS_RESPONSIBLE');
        $this->addSql('DROP INDEX IDX_PRODUCTION_ORDERS_RESPONSIBLE');
        $this->addSql('ALTER TABLE production_orders DROP responsible_id');
    }
}
