<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151126144904 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE removed_user_password (user_id INT NOT NULL, hash VARCHAR(100) NOT NULL, expire_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, reset TINYINT(1) NOT NULL, once_password VARCHAR(100) DEFAULT NULL, used TINYINT(1) NOT NULL, once_expire_at DATETIME DEFAULT NULL, err_num INT NOT NULL, PRIMARY KEY(user_id))');
        $this->addSql('ALTER TABLE removed_user_password ADD CONSTRAINT FK_27EE6A5CA76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE removed_user_password');
    }
}
