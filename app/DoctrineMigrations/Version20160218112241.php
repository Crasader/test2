<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160218112241 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX idx_login_ip ON login_log (ip)');
        $this->addSql('ALTER TABLE login_log MODIFY ip int(10) unsigned NOT NULL AFTER username');
        $this->addSql('ALTER TABLE login_log MODIFY at datetime NOT NULL AFTER ip');
        $this->addSql('ALTER TABLE login_log MODIFY domain int(11) NOT NULL AFTER at');
        $this->addSql('ALTER TABLE login_log MODIFY result SMALLINT UNSIGNED NOT NULL AFTER role');
        $this->addSql('ALTER TABLE login_log MODIFY country varchar(40) DEFAULT NULL AFTER result');
        $this->addSql('ALTER TABLE login_log MODIFY city varchar(40) DEFAULT NULL AFTER country');
        $this->addSql('ALTER TABLE login_log MODIFY language varchar(20) NOT NULL AFTER ipv6');
        $this->addSql('ALTER TABLE login_log MODIFY session_id varchar(50) DEFAULT NULL AFTER proxy4');
        $this->addSql('ALTER TABLE login_log MODIFY host varchar(255) NOT NULL AFTER session_id');
        $this->addSql('ALTER TABLE login_log ADD entrance SMALLINT UNSIGNED DEFAULT NULL AFTER role');
        $this->addSql('ALTER TABLE login_log MODIFY ingress SMALLINT UNSIGNED DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_login_ip ON login_log');
        $this->addSql('ALTER TABLE login_log DROP entrance');
        $this->addSql('ALTER TABLE login_log MODIFY ip int(10) unsigned NOT NULL AFTER role');
        $this->addSql('ALTER TABLE login_log MODIFY host varchar(255) NOT NULL AFTER ipv6');
        $this->addSql('ALTER TABLE login_log MODIFY at datetime NOT NULL AFTER host');
        $this->addSql('ALTER TABLE login_log MODIFY result int(11) NOT NULL AFTER at');
        $this->addSql('ALTER TABLE login_log MODIFY session_id varchar(255) DEFAULT NULL AFTER result');
        $this->addSql('ALTER TABLE login_log MODIFY domain int(11) NOT NULL AFTER session_id');
        $this->addSql('ALTER TABLE login_log MODIFY language varchar(20) NOT NULL AFTER domain');
        $this->addSql('ALTER TABLE login_log MODIFY country varchar(40) DEFAULT NULL AFTER proxy4');
        $this->addSql('ALTER TABLE login_log MODIFY city varchar(40) DEFAULT NULL AFTER country');
        $this->addSql('ALTER TABLE login_log MODIFY ingress INT DEFAULT NULL');
    }
}
