<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170113140226 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE login_log_mobile (login_log_id BIGINT UNSIGNED NOT NULL, name VARCHAR(30) DEFAULT NULL, brand VARCHAR(30) DEFAULT NULL, model VARCHAR(30) DEFAULT NULL, PRIMARY KEY(login_log_id))');
        $this->addSql('ALTER TABLE slide_device ADD os VARCHAR(30) DEFAULT NULL, ADD brand VARCHAR(30) DEFAULT NULL, ADD model VARCHAR(30) DEFAULT NULL');
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('sync-login-log-mobile', 0, '2017-01-01 00:00:00', '2017-01-01 00:00:00', NULL, '同步登入紀錄行動裝置資訊', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE login_log_mobile');
        $this->addSql('ALTER TABLE slide_device DROP os, DROP brand, DROP model');
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-login-log-mobile'");
    }
}