<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link order files to document workflows and create drafts for existing files';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_workflows ADD production_order_file_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DOCUMENT_WORKFLOWS_ORDER_FILE ON document_workflows (production_order_file_id)');
        $this->addSql('ALTER TABLE document_workflows ADD CONSTRAINT FK_DOCUMENT_WORKFLOWS_ORDER_FILE FOREIGN KEY (production_order_file_id) REFERENCES production_order_files (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql("INSERT INTO document_workflows (title, document_type, status, created_by_id, created_at, updated_at, production_order_file_id) SELECT original_name, 'order', 'draft', uploaded_by_id, uploaded_at, uploaded_at, id FROM production_order_files");
        $this->addSql("INSERT INTO document_workflow_events (workflow_id, event_type, comment, created_by_id, created_at) SELECT w.id, 'created', 'Документ создан для ранее загруженного файла заказа.', f.uploaded_by_id, f.uploaded_at FROM document_workflows w JOIN production_order_files f ON f.id = w.production_order_file_id");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_workflows DROP CONSTRAINT FK_DOCUMENT_WORKFLOWS_ORDER_FILE');
        $this->addSql('DROP INDEX UNIQ_DOCUMENT_WORKFLOWS_ORDER_FILE');
        $this->addSql('ALTER TABLE document_workflows DROP production_order_file_id');
    }
}
