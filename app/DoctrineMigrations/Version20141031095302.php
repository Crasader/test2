<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141031095302 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE stat_cash_deposit_withdraw (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, parent_id INT NOT NULL, deposit_amount NUMERIC(16, 4) NOT NULL, deposit_count INT NOT NULL, withdraw_amount NUMERIC(16, 4) NOT NULL, withdraw_count INT NOT NULL, deposit_withdraw_amount NUMERIC(16, 4) NOT NULL, deposit_withdraw_count INT NOT NULL, version INT UNSIGNED NOT NULL, INDEX idx_stat_cash_deposit_withdraw_at_user_id (at, user_id), INDEX idx_stat_cash_deposit_withdraw_domain_at (domain, at), PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE stat_cash_deposit_withdraw");
    }
}
