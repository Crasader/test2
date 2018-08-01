<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170517013250 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql('DROP INDEX uni_ip_blacklist_domain_ip_created_date ON ip_blacklist');
        $this->addSql('CREATE UNIQUE INDEX uni_ip_blacklist_domain_ip_created_date_create_user_login_error ON ip_blacklist (domain, ip, created_date, create_user, login_error)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql('DROP INDEX uni_ip_blacklist_domain_ip_created_date_create_user_login_error ON ip_blacklist');
        $this->addSql('CREATE UNIQUE INDEX uni_ip_blacklist_domain_ip_created_date ON ip_blacklist (domain, ip, created_date)');
    }
}
