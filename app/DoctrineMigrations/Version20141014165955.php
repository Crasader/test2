<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141014165955 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE ip_blacklist ADD create_user TINYINT(1) NOT NULL AFTER ip, ADD login_error TINYINT(1) NOT NULL AFTER create_user");
        $this->addSql("UPDATE ip_blacklist SET create_user = 1");
        $this->addSql("ALTER TABLE domain_config ADD block_login TINYINT(1) NOT NULL AFTER block_create_user");
        $this->addSql("CREATE TABLE login_error_per_ip (id INT AUTO_INCREMENT NOT NULL, ip INT UNSIGNED NOT NULL, at BIGINT UNSIGNED NOT NULL, domain INT NOT NULL, count INT UNSIGNED NOT NULL, UNIQUE INDEX uni_login_error_ip_at_domain (ip, at, domain), PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE ip_blacklist DROP create_user, DROP login_error");
        $this->addSql("ALTER TABLE domain_config DROP block_login");
        $this->addSql("DROP TABLE login_error_per_ip");
    }
}
