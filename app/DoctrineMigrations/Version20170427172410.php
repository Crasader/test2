<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170427172410 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://www.unipaygo.com/index.php/payin/proxy', reop_url = 'https://www.unipaygo.com/index.php/payin/query_order', verify_url = 'payment.https.www.unipaygo.com', upload_key = 1 WHERE id = 61");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor VALUES (61, 7), (61, 9), (61, 10), (61, 11), (61, 13), (61, 16), (61, 19), (61, 217), (61, 220), (61, 221), (61, 222), (61, 226), (61, 228), (61, 321)");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES (61, 'public_key_content', ''), (61, 'private_key_content', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = 61 AND name IN ('public_key_content', 'private_key_content')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 61 AND payment_vendor_id IN (7, 9, 10, 11, 13, 16, 19, 217, 220, 221, 222, 226, 228, 321)");
        $this->addSql("UPDATE payment_gateway SET post_url = 'https://www.unipaygo.com/index.php/payapi/', reop_url = 'https://www.unipaygo.com/index.php/payapi/query_order', verify_url = 'payment.https.www.unipaygo.com', upload_key = 0 WHERE id = 61");
    }
}
