<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161117184124 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE background_process SET name = 'generate-rm-plan-user' WHERE name = 'generate-remove-plan-user'");
        $this->addSql("UPDATE background_process SET name = 'sync-rm-plan-user' WHERE name = 'sync-remove-plan-user'");
        $this->addSql("UPDATE background_process SET name = 'execute-rm-plan' WHERE name = 'remove-plan-user'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE background_process SET name = 'generate-remove-plan-user' WHERE name = 'generate-rm-plan-user'");
        $this->addSql("UPDATE background_process SET name = 'sync-remove-plan-user' WHERE name = 'sync-rm-plan-user'");
        $this->addSql("UPDATE background_process SET name = 'remove-plan-user' WHERE name = 'execute-rm-plan'");
    }
}
