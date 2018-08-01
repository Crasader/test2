<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180417085123 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET withdraw_url = 'http://public.meowpay.io/api/v1/MerchantWithdraw', withdraw_host = 'payment.http.public.meowpay.io', withdraw = 1 WHERE id = 441");
        $this->addSql("INSERT INTO bank_info (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`, `auto_withdraw`) VALUES ('429', 'CG钱包', 'CG', '', '0', '1', '1', '0')");
        $this->addSql("INSERT INTO bank_currency (`id`, `bank_info_id`, `currency`) VALUES ('419', '429', '156')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM bank_currency WHERE id = 419");
        $this->addSql("DELETE FROM bank_info WHERE id = 429");
        $this->addSql("UPDATE payment_gateway SET withdraw_url = '', withdraw_host = '', withdraw = 0 WHERE id = 441");
    }
}
