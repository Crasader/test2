<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151112164519 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('level-transfer', 1, '2015-06-01 00:00:00', '2015-06-01 00:00:00', NULL, '層級轉移/週期: 1秒', 0, 0)");
        $this->addSql("DROP INDEX idx_user_level_level_id ON user_level");
        $this->addSql("CREATE INDEX idx_user_level_level_id_user_id ON user_level (level_id, user_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'level-transfer'");
        $this->addSql("DROP INDEX idx_user_level_level_id_user_id ON user_level");
        $this->addSql("CREATE INDEX idx_user_level_level_id ON user_level (level_id)");
    }
}
