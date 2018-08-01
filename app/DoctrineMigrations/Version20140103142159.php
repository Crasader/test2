<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140103142159 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE merchant_has_bank_info");
        $this->addSql("DROP TABLE payment_gateway_has_bank_info");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE merchant_has_bank_info (merchant_id SMALLINT UNSIGNED NOT NULL, bank_info_id INT NOT NULL, INDEX IDX_9473F9846796D554 (merchant_id), INDEX IDX_9473F984731FA956 (bank_info_id), PRIMARY KEY(merchant_id, bank_info_id))");
        $this->addSql("CREATE TABLE payment_gateway_has_bank_info (payment_gateway_id SMALLINT UNSIGNED NOT NULL, bank_info_id INT NOT NULL, INDEX IDX_51B68F9F62890FD5 (payment_gateway_id), INDEX IDX_51B68F9F731FA956 (bank_info_id), PRIMARY KEY(payment_gateway_id, bank_info_id))");
        $this->addSql("ALTER TABLE merchant_has_bank_info ADD CONSTRAINT FK_9473F9846796D554 FOREIGN KEY (merchant_id) REFERENCES merchant (id)");
        $this->addSql("ALTER TABLE merchant_has_bank_info ADD CONSTRAINT FK_9473F984731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)");
        $this->addSql("ALTER TABLE payment_gateway_has_bank_info ADD CONSTRAINT FK_51B68F9F62890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)");
        $this->addSql("ALTER TABLE payment_gateway_has_bank_info ADD CONSTRAINT FK_51B68F9F731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)");
    }
}
