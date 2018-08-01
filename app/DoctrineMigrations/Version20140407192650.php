<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140407192650 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE payment_gateway ADD support TINYINT(1) NOT NULL, ADD `label` VARCHAR(15) NOT NULL");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'CloudPay' WHERE id = 32");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'PK989' WHERE id = 36");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'PK989S' WHERE id = 37");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'BaoFoo99' WHERE id = 40");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'BaoFoo' WHERE id = 41");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'CardToPay' WHERE id = 47");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'U2bet' WHERE id = 49");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'EasyPay' WHERE id = 50");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'KLTong' WHERE id = 52");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'EPay95' WHERE id = 54");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'JZPlay' WHERE id = 56");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'Weishih' WHERE id = 59");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'CloudIps' WHERE id = 62");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'BooFooII99' WHERE id = 67");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'CJBBank' WHERE id = 68");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'XinYang' WHERE id = 69");
        $this->addSql("UPDATE payment_gateway SET support = 1, label = 'NewBaoFoo' WHERE id = 70");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE payment_gateway DROP support, DROP `label`");
    }
}
