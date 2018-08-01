<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140509155448 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("update `background_process` SET `name` = 'activate-merchant' WHERE `name` = 'activate_merchant'");
        $this->addSql("update `background_process` SET `name` = 'activate-sl-next' WHERE `name` = 'activate_sl_next'");
        $this->addSql("update `background_process` SET `name` = 'backup-user' WHERE `name` = 'backup_user'");
        $this->addSql("update `background_process` SET `name` = 'check-cash-entry' WHERE `name` = 'check_cash_entry'");
        $this->addSql("update `background_process` SET `name` = 'check-cash-error' WHERE `name` = 'check_cash_error'");
        $this->addSql("update `background_process` SET `name` = 'check-cash-fake-entry' WHERE `name` = 'check_cash_fake_entry'");
        $this->addSql("update `background_process` SET `name` = 'check-cash-fake-error' WHERE `name` = 'check_cash_fake_error'");
        $this->addSql("update `background_process` SET `name` = 'check-withdraw' WHERE `name` = 'check_withdraw'");
        $this->addSql("update `background_process` SET `name` = 'message-to-italking' WHERE `name` = 'message_to_italking'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("update `background_process` SET `name` = 'activate_merchant' WHERE `name` = 'activate-merchant'");
        $this->addSql("update `background_process` SET `name` = 'activate_sl_next' WHERE `name` = 'activate-sl-next'");
        $this->addSql("update `background_process` SET `name` = 'backup_user' WHERE `name` = 'backup-user'");
        $this->addSql("update `background_process` SET `name` = 'check_cash_entry' WHERE `name` = 'check-cash-entry'");
        $this->addSql("update `background_process` SET `name` = 'check_cash_error' WHERE `name` = 'check-cash-error'");
        $this->addSql("update `background_process` SET `name` = 'check_cash_fake_entry' WHERE `name` = 'check-cash-fake-entry'");
        $this->addSql("update `background_process` SET `name` = 'check_cash_fake_error' WHERE `name` = 'check-cash-fake-error'");
        $this->addSql("update `background_process` SET `name` = 'check_withdraw' WHERE `name` = 'check-withdraw'");
        $this->addSql("update `background_process` SET `name` = 'message_to_italking' WHERE `name` = 'message-to-italking'");
    }
}
