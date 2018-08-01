<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160830110614 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE user_detail TO user_detail_old');
        $this->addSql('ALTER TABLE user_detail_old DROP FOREIGN KEY FK_4B5464AEA76ED395');
        $this->addSql('RENAME TABLE new_user_detail TO user_detail');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE user_detail TO new_user_detail');
        $this->addSql('ALTER TABLE user_detail_old ADD CONSTRAINT FK_D95BD5D6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('RENAME TABLE user_detail_old TO user_detail');
    }
}
