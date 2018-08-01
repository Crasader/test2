<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160318170046 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE payment_gateway_description (id INT AUTO_INCREMENT NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, name VARCHAR(45) NOT NULL, value VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('1', 'number', ''), ('1', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('4', 'number', ''), ('4', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('5', 'number', ''), ('5', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('6', 'number', ''), ('6', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('8', 'number', ''), ('8', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('12', 'number', ''), ('12', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('16', 'number', ''), ('16', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('18', 'number', ''), ('18', 'private_key', ''), ('18', 'alipayUserName', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('21', 'number', ''), ('21', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('22', 'number', ''), ('22', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('23', 'number', ''), ('23', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('24', 'number', ''), ('24', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('27', 'number', ''), ('27', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('31', 'number', ''), ('31', 'private_key', ''), ('31', 'api_username', ''), ('31', 'api_password', ''), ('31', 'bk_seller_email', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('32', 'number', ''), ('32', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('33', 'number', ''), ('33', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('34', 'number', ''), ('34', 'private_key', ''), ('34', 'virCardNoIn', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('36', 'number', ''), ('36', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('37', 'number', ''), ('37', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('38', 'number', ''), ('38', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('39', 'number', ''), ('39', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('40', 'number', ''), ('40', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('41', 'number', ''), ('41', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('42', 'number', ''), ('42', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('45', 'number', ''), ('45', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('47', 'number', ''), ('47', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('48', 'number', ''), ('48', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('49', 'number', ''), ('49', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('50', 'number', ''), ('50', 'private_key', ''), ('50', 'seller_email', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('51', 'number', ''), ('51', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('52', 'number', ''), ('52', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('53', 'number', ''), ('53', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('54', 'number', ''), ('54', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('55', 'number', ''), ('55', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('56', 'number', ''), ('56', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('58', 'number', ''), ('58', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('59', 'number', ''), ('59', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('60', 'number', ''), ('60', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('61', 'number', ''), ('61', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('62', 'number', ''), ('62', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('63', 'number', ''), ('63', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('64', 'number', ''), ('64', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('65', 'number', ''), ('65', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('67', 'number', ''), ('67', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('68', 'number', ''), ('68', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('69', 'number', ''), ('69', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('70', 'number', ''), ('70', 'private_key', ''), ('70', 'terminalId', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('71', 'number', ''), ('71', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('72', 'number', ''), ('72', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('74', 'number', ''), ('74', 'private_key', ''), ('74', 'businessType', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('75', 'number', ''), ('75', 'private_key', ''), ('75', 'seller_email', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('76', 'number', ''), ('76', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('77', 'number', ''), ('77', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('78', 'number', ''), ('78', 'private_key', ''), ('78', 'account_passward', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('79', 'number', ''), ('79', 'private_key', ''), ('79', 'receiver', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('80', 'number', ''), ('80', 'private_key', ''), ('80', 'client_id', ''), ('80', 'client_secret', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('81', 'number', ''), ('81', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('82', 'number', ''), ('82', 'private_key', ''), ('82', 'Password', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('83', 'number', ''), ('83', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('84', 'number', ''), ('84', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('85', 'number', ''), ('85', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('86', 'number', ''), ('86', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('87', 'number', ''), ('87', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('88', 'number', ''), ('88', 'private_key', ''), ('88', 'platformID', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('89', 'number', ''), ('89', 'private_key', ''), ('89', 'virCardNoIn', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('90', 'number', ''), ('90', 'private_key', ''), ('90', 'Account', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('91', 'number', ''), ('91', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('92', 'number', ''), ('92', 'private_key', ''), ('92', 'appid', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('93', 'number', ''), ('93', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('94', 'number', ''), ('94', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('95', 'number', ''), ('95', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('96', 'number', ''), ('96', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('97', 'number', ''), ('97', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('98', 'number', ''), ('98', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('99', 'number', ''), ('99', 'private_key', ''), ('99', 'Account', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('100', 'number', ''), ('100', 'private_key', ''), ('100', 'terminalId', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('101', 'number', ''), ('101', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('102', 'number', ''), ('102', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('103', 'number', ''), ('103', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('104', 'number', ''), ('104', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('105', 'number', ''), ('105', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('106', 'number', ''), ('106', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('107', 'number', ''), ('107', 'private_key', ''), ('107', 'platformID', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('108', 'number', ''), ('108', 'private_key', ''), ('108', 'platformID', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('109', 'number', ''), ('109', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('110', 'number', ''), ('110', 'private_key', ''), ('110', 'TerminalID', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('111', 'number', ''), ('111', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('114', 'number', ''), ('114', 'private_key', ''), ('114', 'ClerkID', '')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('115', 'number', ''), ('115', 'private_key', ''), ('115', 'platformID', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE payment_gateway_description');
    }
}
