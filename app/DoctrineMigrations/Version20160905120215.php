<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160905120215 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE removed_blacklist (blacklist_id INT NOT NULL, domain INT DEFAULT 0 NOT NULL, whole_domain TINYINT(1) DEFAULT \'1\' NOT NULL, account VARCHAR(36) DEFAULT NULL, identity_card VARCHAR(18) DEFAULT NULL, name_real VARCHAR(100) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(50) DEFAULT NULL, ip INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, note VARCHAR(150) DEFAULT NULL, INDEX idx_removed_blacklist_modified_at (modified_at), PRIMARY KEY(blacklist_id))');
        $this->addSql('DROP INDEX uni_blacklist_whole_domain_ip_created_at ON blacklist');
        $this->addSql('UPDATE blacklist SET domain = 0 WHERE whole_domain = 1 AND domain IS NULL');
        $this->addSql('INSERT INTO removed_blacklist (SELECT id, domain, whole_domain, account, identity_card, name_real, telephone, email, ip, created_at, modified_at, note FROM blacklist where removed = 1)');
        $this->addSql('DELETE FROM blacklist where removed = 1');
        $this->addSql('ALTER TABLE blacklist DROP removed, CHANGE domain domain INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uni_blacklist_domain_ip ON blacklist (domain, ip)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX uni_blacklist_whole_domain_ip_created_at ON blacklist (whole_domain, ip, created_at)');
        $this->addSql('ALTER TABLE blacklist ADD removed TINYINT(1) NOT NULL, CHANGE domain domain INT DEFAULT NULL');
        $this->addSql('INSERT INTO blacklist (domain, whole_domain, account, identity_card, name_real, telephone, email, ip, created_at, modified_at, note, removed) SELECT domain, whole_domain, account, identity_card, name_real, telephone, email, ip, created_at, modified_at, note, 1 FROM removed_blacklist');
        $this->addSql('DROP TABLE removed_blacklist');
        $this->addSql('UPDATE blacklist SET domain = NULL WHERE whole_domain = 1 AND domain = 0');
        $this->addSql('DROP INDEX uni_blacklist_domain_ip ON blacklist');
    }
}
