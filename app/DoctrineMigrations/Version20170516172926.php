<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170516172926 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET name = '新快匯寶掃碼' WHERE id = 101");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor VALUES ('101', '1092')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 101 AND payment_vendor_id = 1092");
        $this->addSql("UPDATE payment_gateway SET name = '新快匯寶微信支付' WHERE id = 101");
    }
}
