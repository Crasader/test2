<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160606170809 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('109', 'Eypal', '盈寶支付', 'https://gateway.eypal.com/Eypal/Gateway', '1', 'https://query.eypal.com/Query/Gateway', 'payment.https.query.eypal.com', '172.26.54.42', '0', 'Eypal', '0', '0', '1', '0', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`currency`, `payment_gateway_id`) VALUES ('156', '109')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('109', '1'), ('109', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor`(`payment_gateway_id`, `payment_vendor_id`) VALUES ('109', '1'), ('109', '2'), ('109', '3'), ('109', '4'), ('109', '5'), ('109', '6'), ('109', '8'), ('109', '9'), ('109', '10'), ('109', '11'), ('109', '12'), ('109', '13'), ('109', '14'), ('109', '15'), ('109', '16'), ('109', '17'), ('109', '19'), ('109', '217'), ('109', '228'), ('109', '278'), ('109', '1090')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '109' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '228', '278', '1090')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '109' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '109'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '109'");
    }
}
