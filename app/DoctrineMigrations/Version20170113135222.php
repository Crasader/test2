<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170113135222 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://service.payquickdraw.com/api/gateway', `reop_url` = 'https://service.payquickdraw.com/api/gateway', `upload_key` = '1' WHERE `id` = 152");
        $this->addSql("INSERT INTO `payment_gateway_description` (payment_gateway_id, name, value) VALUES ('152', 'private_key_content', ''), ('152', 'public_key_content', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '152' AND `name` IN ('private_key_content', 'public_key_content')");
        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://service.payquickdraw.com/api/md5/gateway', `reop_url` = 'https://service.payquickdraw.com/api/md5/gateway', `upload_key` = '0' WHERE `id` = 152");
    }
}
