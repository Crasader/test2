<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160506173104 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE new_user_detail (user_id INT NOT NULL, nickname VARCHAR(36) NOT NULL, name_real VARCHAR(100) NOT NULL, name_chinese VARCHAR(36) NOT NULL, name_english VARCHAR(36) NOT NULL, country VARCHAR(30) NOT NULL, passport VARCHAR(18) NOT NULL, identity_card VARCHAR(18) NOT NULL, driver_license VARCHAR(18) NOT NULL, insurance_card VARCHAR(18) NOT NULL, health_card VARCHAR(18) NOT NULL, birthday DATETIME DEFAULT NULL, telephone VARCHAR(20) NOT NULL, qq_num VARCHAR(16) NOT NULL, note VARCHAR(150) NOT NULL, password VARCHAR(10) NOT NULL, INDEX idx_user_detail_name_real_2 (name_real), INDEX idx_user_detail_name_chinese_2 (name_chinese), INDEX idx_user_detail_name_english_2 (name_english), INDEX idx_user_detail_passport_2 (passport), INDEX idx_user_detail_identity_card_2 (identity_card), INDEX idx_user_detail_qq_num_2 (qq_num), INDEX idx_user_detail_telephone_2 (telephone), PRIMARY KEY(user_id))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE new_user_detail');
    }
}
