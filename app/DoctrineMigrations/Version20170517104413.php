<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170517104413 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://payment.rfupayadv.com/prod/commgr/control/inPayService', reop_url = 'http://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry', verify_url = 'payment.http.portal.rfupayadv.com' WHERE id = 118");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://payment.rfupay.com/prod/commgr/control/inPayService', reop_url = 'https://portal.rfupay.com/Main/api_enquiry/orderEnquiry', verify_url = 'payment.https.portal.rfupay.com' WHERE id = 118");
    }
}
