<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180710202502 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `auto_remit` SET `name` = 'BB自動認款1.0(已停用)' WHERE `id` = '2'");
        $this->addSql("UPDATE `auto_remit` SET `name` = 'BB自動認款' WHERE `id` = '4'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `auto_remit` SET `name` = 'BB自動認款2.0' WHERE `id` = '4'");
        $this->addSql("UPDATE `auto_remit` SET `name` = 'BB自動認款' WHERE `id` = '2'");
    }
}
