<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180531014243 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://public.cgpay.io/api/v1/BuildGlobalPayOrder', verify_url = 'payment.http.public.cgpay.io', withdraw_url = 'http://public.cgpay.io/api/v1/MerchantWithdraw', withdraw_host = 'payment.http.public.cgpay.io' WHERE id = 441");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://public.meowpay.io/api/v1/BuildGlobalPayOrder', verify_url = 'payment.http.public.meowpay.io', withdraw_url = 'http://public.meowpay.io/api/v1/MerchantWithdraw', withdraw_host = 'payment.http.public.meowpay.io' WHERE id = 441");
    }
}
