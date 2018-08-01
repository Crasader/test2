<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150914174316 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_card_order (merchant_card_id INT UNSIGNED NOT NULL, order_id SMALLINT UNSIGNED NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(merchant_card_id))');
        $this->addSql('CREATE TABLE merchant_card_stat (id INT UNSIGNED AUTO_INCREMENT NOT NULL, merchant_card_id INT UNSIGNED NOT NULL, count INT UNSIGNED NOT NULL, total NUMERIC(16, 4) NOT NULL, at BIGINT UNSIGNED NOT NULL, domain INT NOT NULL, INDEX IDX_7E51F2DF93F2EFBB (merchant_card_id), INDEX idx_merchant_card_stat_domain (domain), UNIQUE INDEX uni_merchant_card_stat (merchant_card_id, at), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE merchant_card_record (id INT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, created_at BIGINT UNSIGNED NOT NULL, msg VARCHAR(2000) NOT NULL, INDEX idx_merchant_card_record_created_at (created_at), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE merchant_card (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, alias VARCHAR(45) NOT NULL, number VARCHAR(80) NOT NULL, enable TINYINT(1) NOT NULL, approved TINYINT(1) NOT NULL, currency SMALLINT UNSIGNED NOT NULL, private_key VARCHAR(512) NOT NULL, shop_url VARCHAR(100) NOT NULL, web_url VARCHAR(100) NOT NULL, full_set TINYINT(1) NOT NULL, created_by_admin TINYINT(1) NOT NULL, suspend TINYINT(1) NOT NULL, removed TINYINT(1) NOT NULL, bind_shop TINYINT(1) NOT NULL, domain INT NOT NULL, INDEX IDX_571757D862890FD5 (payment_gateway_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE merchant_card_has_payment_method (merchant_card_id INT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_FFF86B2B93F2EFBB (merchant_card_id), INDEX IDX_FFF86B2B5AA1164F (payment_method_id), PRIMARY KEY(merchant_card_id, payment_method_id))');
        $this->addSql('CREATE TABLE merchant_card_has_payment_vendor (merchant_card_id INT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_548362BD93F2EFBB (merchant_card_id), INDEX IDX_548362BDB52AC15B (payment_vendor_id), PRIMARY KEY(merchant_card_id, payment_vendor_id))');
        $this->addSql('CREATE TABLE merchant_card_key (id INT AUTO_INCREMENT NOT NULL, merchant_card_id INT UNSIGNED NOT NULL, key_type VARCHAR(20) NOT NULL, file_content VARCHAR(4096) NOT NULL, INDEX IDX_BD645BD193F2EFBB (merchant_card_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE merchant_card_extra (merchant_card_id INT UNSIGNED NOT NULL, name VARCHAR(45) NOT NULL, value VARCHAR(100) NOT NULL, INDEX IDX_17643B7393F2EFBB (merchant_card_id), PRIMARY KEY(merchant_card_id, name))');
        $this->addSql('ALTER TABLE merchant_card_stat ADD CONSTRAINT FK_7E51F2DF93F2EFBB FOREIGN KEY (merchant_card_id) REFERENCES merchant_card (id)');
        $this->addSql('ALTER TABLE merchant_card ADD CONSTRAINT FK_571757D862890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)');
        $this->addSql('ALTER TABLE merchant_card_has_payment_method ADD CONSTRAINT FK_FFF86B2B93F2EFBB FOREIGN KEY (merchant_card_id) REFERENCES merchant_card (id)');
        $this->addSql('ALTER TABLE merchant_card_has_payment_method ADD CONSTRAINT FK_FFF86B2B5AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)');
        $this->addSql('ALTER TABLE merchant_card_has_payment_vendor ADD CONSTRAINT FK_548362BD93F2EFBB FOREIGN KEY (merchant_card_id) REFERENCES merchant_card (id)');
        $this->addSql('ALTER TABLE merchant_card_has_payment_vendor ADD CONSTRAINT FK_548362BDB52AC15B FOREIGN KEY (payment_vendor_id) REFERENCES payment_vendor (id)');
        $this->addSql('ALTER TABLE merchant_card_key ADD CONSTRAINT FK_BD645BD193F2EFBB FOREIGN KEY (merchant_card_id) REFERENCES merchant_card (id)');
        $this->addSql('ALTER TABLE merchant_card_extra ADD CONSTRAINT FK_17643B7393F2EFBB FOREIGN KEY (merchant_card_id) REFERENCES merchant_card (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_card_order');
        $this->addSql('DROP TABLE merchant_card_stat');
        $this->addSql('DROP TABLE merchant_card_record');
        $this->addSql('DROP TABLE merchant_card_has_payment_method');
        $this->addSql('DROP TABLE merchant_card_has_payment_vendor');
        $this->addSql('DROP TABLE merchant_card_key');
        $this->addSql('DROP TABLE merchant_card_extra');
        $this->addSql('DROP TABLE merchant_card');
    }
}
