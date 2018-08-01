<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160413110725 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://spayment.ikkpay.com/onlinebank/createOrder.do', `reop_url` = 'https://payment.ikkpay.com/ebank/queryOrder.do', `verify_url` = 'payment.https.payment.ikkpay.com' WHERE `id` = '95'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://payment.kklpay.com/ebank/pay.do', `reop_url` = 'https://payment.kklpay.com/query/queryOrder.do', `verify_url` = 'payment.https.payment.kklpay.com' WHERE `id` = '95'");
    }
}
