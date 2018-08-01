<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140428175901 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("update `background_process` SET `name` = 'run-card-poper' WHERE `name` = 'run_card_poper'");
        $this->addSql("update `background_process` SET `name` = 'run-card-sync' WHERE `name` = 'run_card_sync'");
        $this->addSql("update `background_process` SET `name` = 'run-cashfake-poper' WHERE `name` = 'run_cashfake_poper'");
        $this->addSql("update `background_process` SET `name` = 'run-cashfake-sync' WHERE `name` = 'run_cashfake_sync'");
        $this->addSql("update `background_process` SET `name` = 'run-cash-poper' WHERE `name` = 'run_cash_poper'");
        $this->addSql("update `background_process` SET `name` = 'run-cash-sync' WHERE `name` = 'run_cash_sync'");
        $this->addSql("update `background_process` SET `name` = 'run-credit-poper' WHERE `name` = 'run_credit_poper'");
        $this->addSql("update `background_process` SET `name` = 'run-credit-sync' WHERE `name` = 'run_credit_sync'");
        $this->addSql("update `background_process` SET `name` = 'sync-cashfake-his-poper' WHERE `name` = 'sync_cashfake_his_poper'");
        $this->addSql("update `background_process` SET `name` = 'sync-his-poper' WHERE `name` = 'sync_his_poper'");
        $this->addSql("update `background_process` SET `name` = 'send-maintain-message' WHERE `name` = 'send_maintain_message'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("update `background_process` SET `name` = 'run_card_poper' WHERE `name` = 'run-card-poper'");
        $this->addSql("update `background_process` SET `name` = 'run_card_sync' WHERE `name` = 'run-card-sync'");
        $this->addSql("update `background_process` SET `name` = 'run_cashfake_poper' WHERE `name` = 'run-cashfake-poper'");
        $this->addSql("update `background_process` SET `name` = 'run_cashfake_sync' WHERE `name` = 'run-cashfake-sync'");
        $this->addSql("update `background_process` SET `name` = 'run_cash_poper' WHERE `name` = 'run-cash-poper'");
        $this->addSql("update `background_process` SET `name` = 'run_cash_sync' WHERE `name` = 'run-cash-sync'");
        $this->addSql("update `background_process` SET `name` = 'run_credit_poper' WHERE `name` = 'run-credit-poper'");
        $this->addSql("update `background_process` SET `name` = 'run_credit_sync' WHERE `name` = 'run-credit-sync'");
        $this->addSql("update `background_process` SET `name` = 'sync_cashfake_his_poper' WHERE `name` = 'sync-cashfake-his-poper'");
        $this->addSql("update `background_process` SET `name` = 'sync_his_poper' WHERE `name` = 'sync-his-poper'");
        $this->addSql("update `background_process` SET `name` = 'send_maintain_message' WHERE `name` = 'send-maintain-message'");
    }
}
