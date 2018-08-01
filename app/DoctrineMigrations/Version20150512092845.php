<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150512092845 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('296', '微信支付', '微信', 'http://weixin.qq.com/', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('296', '1', '微信支付')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('285', '296', '156')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '285'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '296'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '296'");
    }
}
