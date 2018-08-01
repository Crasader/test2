<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151215170741 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_withdraw_ip_strategy (id INT UNSIGNED AUTO_INCREMENT NOT NULL, merchant_withdraw_id INT UNSIGNED NOT NULL, country_id INT UNSIGNED NOT NULL, region_id INT UNSIGNED DEFAULT NULL, city_id INT UNSIGNED DEFAULT NULL, INDEX IDX_8FB376CF4B355324 (merchant_withdraw_id), INDEX IDX_8FB376CFF92F3E70 (country_id), INDEX IDX_8FB376CF98260155 (region_id), INDEX IDX_8FB376CF8BAC62AF (city_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CF4B355324 FOREIGN KEY (merchant_withdraw_id) REFERENCES merchant_withdraw (id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CFF92F3E70 FOREIGN KEY (country_id) REFERENCES geoip_country (country_id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CF98260155 FOREIGN KEY (region_id) REFERENCES geoip_region (region_id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CF8BAC62AF FOREIGN KEY (city_id) REFERENCES geoip_city (city_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_withdraw_ip_strategy');
    }
}
