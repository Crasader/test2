<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180712092045 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://www.gzbaoqing.com/api.php?s=/H5Pay/h5_Pay.html' WHERE id = 526");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('526', '3')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('526', '1098')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '526' AND mlv.`payment_vendor_id` = '1090'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '526' AND `payment_vendor_id` = '1090'");
        $this->addSql("INSERT INTO payment_gateway_random_float_vendor (`payment_gateway_id`, `payment_vendor_id`) VALUE ('526', '1092'), ('526', '1098')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_random_float_vendor` WHERE `payment_gateway_id` = '526' AND `payment_vendor_id` IN ('1092', '1098')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('526', '1090')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '526' AND `payment_vendor_id` = '1098'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '526' AND `payment_method_id` = '3'");
        $this->addSql("UPDATE payment_gateway SET post_url = 'http://www.gzbaoqing.com/api.php?s=/Scanpay/begin_Pay.html' WHERE id = 526");
    }
}
