<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160406161032 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('sync-obtain-reward', '1', '2016-01-01 00:00:00', '2016-01-01 00:00:00', NULL, '同步抽中紅包,1/sec', '0', '0')");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('op-obtain-reward', '1', '2016-01-01 00:00:00', '2016-01-01 00:00:00', NULL, '抽中紅包派彩,1/min', '0', '0')");
        $this->addSql('ALTER TABLE reward_entry CHANGE id id BIGINT UNSIGNED NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM background_process WHERE name = 'sync-obtain-reward'");
        $this->addSql("DELETE FROM background_process WHERE name = 'obtain-reward-operation'");
        $this->addSql('ALTER TABLE reward_entry CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL');
    }
}
