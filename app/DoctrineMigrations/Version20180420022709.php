<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180420022709 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE bank MODIFY COLUMN `account` varchar(42)");
        $this->addSql("ALTER TABLE cash_withdraw_entry MODIFY COLUMN `account` varchar(42)");
        $this->addSql("ALTER TABLE account_log MODIFY COLUMN `account_no` varchar(42)");
        $this->addSql("ALTER TABLE user_stat MODIFY COLUMN `last_withdraw_account` varchar(42)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE user_stat MODIFY COLUMN `last_withdraw_account` varchar(36)");
        $this->addSql("ALTER TABLE account_log MODIFY COLUMN `account_no` varchar(36)");
        $this->addSql("ALTER TABLE cash_withdraw_entry MODIFY COLUMN `account` varchar(36)");
        $this->addSql("ALTER TABLE bank MODIFY COLUMN `account` varchar(36)");
    }
}
