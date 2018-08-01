<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180727060518 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://120.78.215.187:8899/rest/v1.0/paybar/pay', `verify_url` = 'payment.http.120.78.215.187' WHERE `id` = '578'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://120.78.75.130:8096/rest/v1.0/paybar/pay', `verify_url` = 'payment.http.120.78.75.130' WHERE `id` = '578'");
    }
}
