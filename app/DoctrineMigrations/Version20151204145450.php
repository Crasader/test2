<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151204145450 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_login_log_result ON login_log');
        $this->addSql('DROP INDEX idx_login_log_domain ON login_log');
        $this->addSql('CREATE INDEX idx_login_at_ip ON login_log (at, ip)');
        $this->addSql('CREATE INDEX idx_login_username ON login_log (username)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX idx_login_log_domain ON login_log (domain)');
        $this->addSql('CREATE INDEX idx_login_log_result ON login_log (result)');
        $this->addSql('DROP INDEX idx_login_at_ip ON login_log');
        $this->addSql('DROP INDEX idx_login_username ON login_log');
    }
}
