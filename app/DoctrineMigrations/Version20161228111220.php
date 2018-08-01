<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161228111220 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://gateway.hnapay.com/website/pay.htm', `reop_url` = 'https://gateway.hnapay.com/website/queryOrderResult.htm', `verify_url` = 'payment.https.gateway.hnapay.com' WHERE `id` = 27");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://www.hnapay.com/website/pay.htm', `reop_url` = 'https://www.hnapay.com/website/queryOrderResult.htm', `verify_url` = 'payment.https.www.hnapay.com' WHERE `id` = 27");
    }
}
