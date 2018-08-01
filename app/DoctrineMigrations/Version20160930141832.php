<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160930141832 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY FK_EC6935F98BAC62AF');
        $this->addSql('ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY FK_EC6935F998260155');
        $this->addSql('ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY FK_EC6935F9F92F3E70');
        $this->addSql('DROP INDEX IDX_EC6935F9F92F3E70 ON merchant_ip_strategy');
        $this->addSql('DROP INDEX IDX_EC6935F998260155 ON merchant_ip_strategy');
        $this->addSql('DROP INDEX IDX_EC6935F98BAC62AF ON merchant_ip_strategy');
        $this->addSql('ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY FK_EC6935F93616FF93');
        $this->addSql('ALTER TABLE merchant_ip_strategy CHANGE city_id city_id INT DEFAULT NULL, CHANGE region_id region_id INT DEFAULT NULL, CHANGE country_id country_id INT NOT NULL');
        $this->addSql('DROP INDEX IDX_EC6935F93616FF93 ON merchant_ip_strategy');
        $this->addSql('CREATE INDEX IDX_EC6935F96796D554 ON merchant_ip_strategy (merchant_id)');
        $this->addSql('ALTER TABLE merchant_ip_strategy ADD CONSTRAINT FK_EC6935F93616FF93 FOREIGN KEY (merchant_id) REFERENCES merchant (id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy DROP FOREIGN KEY FK_8FB376CF8BAC62AF');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy DROP FOREIGN KEY FK_8FB376CF98260155');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy DROP FOREIGN KEY FK_8FB376CFF92F3E70');
        $this->addSql('DROP INDEX IDX_8FB376CFF92F3E70 ON merchant_withdraw_ip_strategy');
        $this->addSql('DROP INDEX IDX_8FB376CF98260155 ON merchant_withdraw_ip_strategy');
        $this->addSql('DROP INDEX IDX_8FB376CF8BAC62AF ON merchant_withdraw_ip_strategy');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy CHANGE city_id city_id INT DEFAULT NULL, CHANGE region_id region_id INT DEFAULT NULL, CHANGE country_id country_id INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY FK_EC6935F93616FF93');
        $this->addSql('ALTER TABLE merchant_ip_strategy CHANGE country_id country_id INT UNSIGNED NOT NULL, CHANGE region_id region_id INT UNSIGNED DEFAULT NULL, CHANGE city_id city_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE merchant_ip_strategy ADD CONSTRAINT FK_EC6935F98BAC62AF FOREIGN KEY (city_id) REFERENCES geoip_city (city_id)');
        $this->addSql('ALTER TABLE merchant_ip_strategy ADD CONSTRAINT FK_EC6935F998260155 FOREIGN KEY (region_id) REFERENCES geoip_region (region_id)');
        $this->addSql('ALTER TABLE merchant_ip_strategy ADD CONSTRAINT FK_EC6935F9F92F3E70 FOREIGN KEY (country_id) REFERENCES geoip_country (country_id)');
        $this->addSql('CREATE INDEX IDX_EC6935F9F92F3E70 ON merchant_ip_strategy (country_id)');
        $this->addSql('CREATE INDEX IDX_EC6935F998260155 ON merchant_ip_strategy (region_id)');
        $this->addSql('CREATE INDEX IDX_EC6935F98BAC62AF ON merchant_ip_strategy (city_id)');
        $this->addSql('DROP INDEX IDX_EC6935F96796D554 ON merchant_ip_strategy');
        $this->addSql('CREATE INDEX IDX_EC6935F93616FF93 ON merchant_ip_strategy (merchant_id)');
        $this->addSql('ALTER TABLE merchant_ip_strategy ADD CONSTRAINT FK_EC6935F93616FF93 FOREIGN KEY (merchant_id) REFERENCES merchant (id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy CHANGE country_id country_id INT UNSIGNED NOT NULL, CHANGE region_id region_id INT UNSIGNED DEFAULT NULL, CHANGE city_id city_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CF8BAC62AF FOREIGN KEY (city_id) REFERENCES geoip_city (city_id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CF98260155 FOREIGN KEY (region_id) REFERENCES geoip_region (region_id)');
        $this->addSql('ALTER TABLE merchant_withdraw_ip_strategy ADD CONSTRAINT FK_8FB376CFF92F3E70 FOREIGN KEY (country_id) REFERENCES geoip_country (country_id)');
        $this->addSql('CREATE INDEX IDX_8FB376CFF92F3E70 ON merchant_withdraw_ip_strategy (country_id)');
        $this->addSql('CREATE INDEX IDX_8FB376CF98260155 ON merchant_withdraw_ip_strategy (region_id)');
        $this->addSql('CREATE INDEX IDX_8FB376CF8BAC62AF ON merchant_withdraw_ip_strategy (city_id)');
    }
}
