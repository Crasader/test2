<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170111103245 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.jinkavip.com/gateway.do?m=order', reop_url = 'http://api.jinkavip.com/qrcodeQuery', verify_url = 'payment.http.api.jinkavip.com' WHERE id = 126");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://112.74.230.8:8081/posp-api/gateway.do?m=order', reop_url = 'http://112.74.230.8:8081/posp-api/qrcodeQuery', verify_url = 'payment.http.112.74.230.8' WHERE id = 126");
    }
}
