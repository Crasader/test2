<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180425032909 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("CREATE TABLE withdraw_error (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, entry_id BIGINT UNSIGNED NOT NULL, at BIGINT UNSIGNED NOT NULL, error_message VARCHAR(2048) NOT NULL, error_code BIGINT UNSIGNED NOT NULL, INDEX idx_withdraw_error_at (at), PRIMARY KEY(id))");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('send-auto-withdraw-request', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '提交出款請求, 1/sec', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM background_process WHERE name = 'send-auto-withdraw-request'");
        $this->addSql("DROP TABLE withdraw_error");
    }
}
