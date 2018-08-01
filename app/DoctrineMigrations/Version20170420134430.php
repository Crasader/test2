<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170420134430 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://vip.iyibank.com/pay/gateway', reop_url = 'https://vip.iyibank.com/pay/orderquery', verify_url = 'payment.https.vip.iyibank.com' WHERE id = 151");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://www.iyibank.com/pay/gateway', reop_url = 'https://www.iyibank.com/pay/orderquery', verify_url = 'payment.https.www.iyibank.com' WHERE id = 151");
    }
}
