<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160516112714 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://www.unipaygo.com/index.php/payapi/', `reop_url` = 'https://www.unipaygo.com/index.php/payapi/query_order', `verify_url` = 'payment.https.www.unipaygo.com' WHERE `id` = 61");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://www.unipaygo.com/index.php/payapi/', `reop_url` = 'http://www.unipaygo.com/index.php/payapi/query_order', `verify_url` = 'payment.http.www.unipaygo.com' WHERE `id` = 61");
    }
}
