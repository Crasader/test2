<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170822110358 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://gateway.555pay.com/native/com.opentech.cloud.pay.trade.create/1.0.0', reop_url = 'https://gateway.555pay.com/native/com.opentech.cloud.pay.trade.query/1.0.0', verify_url = 'payment.https.gateway.555pay.com' WHERE id = 196");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://gw.555pay.com/native/com.opentech.cloud.pay.trade.create/1.0.0', reop_url = 'https://gw.555pay.com/native/com.opentech.cloud.pay.trade.query/1.0.0', verify_url = 'payment.https.gw.555pay.com' WHERE id = 196");
    }
}
