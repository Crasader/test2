<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141210120521 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE domain_config ADD login_code VARCHAR(50) NOT NULL AFTER block_login");
        $this->addSql("UPDATE domain_config dc SET dc.login_code = (SELECT lc.code FROM login_code lc WHERE lc.domain = dc.domain);");
        $this->addSql("INSERT INTO domain_config (domain, block_create_user, block_login, login_code) SELECT domain, '' as block_create_user, '' as block_login, code FROM login_code as lc WHERE NOT EXISTS (SELECT dc.domain FROM domain_config as dc WHERE lc.domain = dc.domain)");
        $this->addSql("CREATE UNIQUE INDEX unique_code ON domain_config (login_code)");
        $this->addSql("DROP TABLE login_code");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE login_code (domain INT NOT NULL, code VARCHAR(50) NOT NULL, UNIQUE INDEX uni_code (code), PRIMARY KEY(domain))");
        $this->addSql("INSERT INTO login_code (domain, code) SELECT domain, login_code FROM domain_config WHERE login_code != ''");
        $this->addSql("DROP INDEX unique_code ON domain_config");
        $this->addSql("UPDATE domain_config dc SET dc.login_code = ''");
        $this->addSql("ALTER TABLE domain_config DROP login_code");
    }
}
