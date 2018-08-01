<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180227050647 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://api.p9pay.com/gateway.do?m=order', `reop_url` = 'http://api.p9pay.com/gateway.do?m=query', `verify_url` = 'payment.http.api.p9pay.com' WHERE `id` = '182'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://api.jb8pay.com/gateway.do?m=order', `reop_url` = 'http://api.jb8pay.com/gateway.do?m=query', `verify_url` = 'payment.http.api.jb8pay.com' WHERE `id` = '182'");
    }
}
