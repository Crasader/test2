<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170718084331 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE weixin_product');
        $this->addSql("DELETE FROM background_process WHERE name = 'weixin-notify'");
        $this->addSql("DELETE FROM background_process WHERE name = 'weixin-product-notify'");
        $this->addSql("DELETE FROM background_process WHERE name = 'update-weixin-product'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('update-weixin-product', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新微信支付商品明細, 每天 00:00', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('weixin-product-notify', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '通知微信訂單商品明細, 60秒', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('weixin-notify', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '通知微信交易結果, 60秒', 0, 0)");
        $this->addSql('CREATE TABLE weixin_product (id INT UNSIGNED AUTO_INCREMENT NOT NULL, merchant_id INT UNSIGNED NOT NULL, category VARCHAR(30) NOT NULL COLLATE utf8_general_ci, name VARCHAR(100) NOT NULL COLLATE utf8_general_ci, price NUMERIC(16, 4) NOT NULL, PRIMARY KEY(id))');
    }
}
