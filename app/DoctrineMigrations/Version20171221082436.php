<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171221082436 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://gateway.gopay.com.cn' WHERE `id` = '89'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('89', '3')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('89', '1003'), ('89', '1005'), ('89', '1006'), ('89', '1007'), ('89', '1009'), ('89', '1011'), ('89', '1013'), ('89', '1015'), ('89', '1017'), ('89', '1018')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '89' AND `payment_vendor_id` IN ('1003', '1005', '1006', '1007', '1009', '1011', '1013', '1015', '1017', '1018')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '89' AND `payment_method_id` = '3'");
        $this->addSql("UPDATE payment_gateway SET `post_url` = 'https://gateway.gopay.com.cn/Trans/WebClientAction.do' WHERE id = '89'");
    }
}
