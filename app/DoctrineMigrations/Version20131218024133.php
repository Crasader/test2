<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131218024133 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE UNIQUE INDEX uni_user_group_sharelimit ON share_limit (user_id, group_num)");
        $this->addSql("CREATE UNIQUE INDEX uni_user_group_sharelimit_next ON share_limit_next (user_id, group_num)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX uni_user_group_sharelimit ON share_limit");
        $this->addSql("DROP INDEX uni_user_group_sharelimit_next ON share_limit_next");
    }
}
