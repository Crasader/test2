<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160303094539 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_has_deposit_withdraw (user_id INT NOT NULL, at DATETIME NOT NULL, deposit TINYINT(1) NOT NULL, withdraw TINYINT(1) NOT NULL, PRIMARY KEY(user_id))');
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('sync-user-deposit-withdraw', 1, '2016-01-01 00:00:00', '2016-01-01 00:00:00', '2016-01-01 00:00:00', '同步使用者現在存提款紀錄, 1/sec', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_has_deposit_withdraw');
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-user-deposit-withdraw'");
    }
}
