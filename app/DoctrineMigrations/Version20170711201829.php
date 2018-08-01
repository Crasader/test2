<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170711201829 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("CREATE TABLE user_has_api_transfer_in_out (user_id bigint(20) unsigned NOT NULL, api_transfer_in TINYINT(1) NOT NULL, api_transfer_out TINYINT(1) NOT NULL, PRIMARY KEY(user_id))");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('sync-user-api-transfer-in-out', 1, '2017-01-01 00:00:00', '2017-01-01 00:00:00', '2017-01-01 00:00:00', '同步使用者api-transfer-in-out紀錄, 1/sec', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DROP TABLE user_has_api_transfer_in_out");
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-user-api-transfer-in-out'");
    }
}
