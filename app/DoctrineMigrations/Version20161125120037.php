<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161125120037 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE slide_device (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, app_id VARCHAR(100) NOT NULL, hash VARCHAR(100) NOT NULL, enabled tinyint(1) NOT NULL, err_num INT NOT NULL, UNIQUE INDEX uni_slide_device_app (app_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE slide_binding (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, device_id BIGINT UNSIGNED DEFAULT NULL, name VARCHAR(100) DEFAULT NULL, binding_token VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, err_num INT NOT NULL, INDEX IDX_824FB62CA76ED395 (user_id), INDEX IDX_824FB62C94A4C7D4 (device_id), UNIQUE INDEX uni_slide_binding_user_device (user_id, device_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE slide_binding ADD CONSTRAINT FK_824FB62CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slide_binding ADD CONSTRAINT FK_824FB62C94A4C7D4 FOREIGN KEY (device_id) REFERENCES slide_device (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE slide_binding DROP FOREIGN KEY FK_824FB62C94A4C7D4');
        $this->addSql('DROP TABLE slide_device');
        $this->addSql('DROP TABLE slide_binding');
    }
}
