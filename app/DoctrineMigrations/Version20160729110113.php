<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160729110113 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE new_user_email (user_id INT NOT NULL, email VARCHAR(254) NOT NULL, confirm TINYINT(1) NOT NULL, confirm_at DATETIME DEFAULT NULL, INDEX idx_user_email_email (email), PRIMARY KEY(user_id))');
        $this->addSql("ALTER TABLE new_user_email ADD CONSTRAINT `FK_550272CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)");
        $this->addSql('CREATE TABLE new_removed_user_email (user_id INT NOT NULL, email VARCHAR(254) DEFAULT NULL, confirm TINYINT(1) NOT NULL, confirm_at DATETIME DEFAULT NULL, PRIMARY KEY(user_id))');
        $this->addSql("ALTER TABLE new_removed_user_email ADD CONSTRAINT `FK_2DE272D5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `removed_user` (`user_id`)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE new_user_email');
        $this->addSql('DROP TABLE new_removed_user_email');
    }
}
