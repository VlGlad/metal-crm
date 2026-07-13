<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Links OTK inspections to shift task items for unified production progress.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE otk_inspections ADD shift_task_item_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_OTK_INSPECTIONS_SHIFT_TASK_ITEM ON otk_inspections (shift_task_item_id)');
        $this->addSql('ALTER TABLE otk_inspections ADD CONSTRAINT FK_OTK_INSPECTIONS_SHIFT_TASK_ITEM FOREIGN KEY (shift_task_item_id) REFERENCES shift_task_items (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE otk_inspections DROP CONSTRAINT FK_OTK_INSPECTIONS_SHIFT_TASK_ITEM');
        $this->addSql('DROP INDEX IDX_OTK_INSPECTIONS_SHIFT_TASK_ITEM');
        $this->addSql('ALTER TABLE otk_inspections DROP shift_task_item_id');
    }
}