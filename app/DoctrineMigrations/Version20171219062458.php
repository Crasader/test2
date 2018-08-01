<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171219062458 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `name` = '新E付', `post_url` = 'newepay.online/' WHERE `id` = '222'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('222', '3')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('222', '1003'), ('222', '1004'), ('222', '1005'), ('222', '1006'), ('222', '1007'), ('222', '1008'), ('222', '1009'), ('222', '1010'), ('222', '1011'), ('222', '1012'), ('222', '1013'), ('222', '1014'), ('222', '1015'), ('222', '1016'), ('222', '1017'), ('222', '1018'), ('222', '1019')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '222' AND `payment_vendor_id` IN ('1003', '1004', '1005', '1006', '1007', '1008', '1009', '1010', '1011', '1012', '1013', '1014', '1015', '1016', '1017', '1018', '1019')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '222' AND `payment_method_id` = '3'");
        $this->addSql("UPDATE payment_gateway SET `name` = '新E付網銀', `post_url` = 'https://gateway.newepay.online/gateway/carpay/V2.0' WHERE id = '222'");
    }
}
