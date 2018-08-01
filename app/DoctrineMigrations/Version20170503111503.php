<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170503111503 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE last_login TO last_login_old, last_login_new TO last_login');
        $this->addSql('DROP TABLE last_login_old');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE last_login_old (user_id INT NOT NULL, ip INT UNSIGNED NOT NULL, err_num INT NOT NULL, at DATETIME DEFAULT NULL, INDEX idx_last_login_ip (ip), PRIMARY KEY(user_id))');
        $this->addSql('RENAME TABLE last_login TO last_login_new, last_login_old TO last_login');
    }
}
