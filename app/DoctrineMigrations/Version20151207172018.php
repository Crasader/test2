<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151207172018 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE domain_config ADD block_test_user TINYINT(1) NOT NULL AFTER block_login');
        $this->addSql('INSERT INTO domain_total_test (domain, total_test) SELECT domain, 0 FROM domain_config');
        $this->addSql('UPDATE domain_total_test d INNER JOIN ( SELECT domain, count(id) AS counts FROM user WHERE test = 1 AND hidden_test = 0 AND role = 1 GROUP BY domain) u ON d.domain = u.domain SET d.total_test = u.counts;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE domain_config DROP block_test_user');
        $this->addSql('DELETE FROM domain_total_test WHERE domain IN (SELECT domain FROM domain_config)');
    }
}
