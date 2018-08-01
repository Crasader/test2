<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160831153027 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE user_email TO user_email_old');
        $this->addSql('ALTER TABLE user_email_old DROP FOREIGN KEY FK_550872CA76ED395');
        $this->addSql('RENAME TABLE new_user_email TO user_email');
        $this->addSql('RENAME TABLE removed_user_email TO removed_user_email_old');
        $this->addSql('ALTER TABLE removed_user_email_old DROP FOREIGN KEY FK_2DE8C2D5A76ED395');
        $this->addSql('RENAME TABLE new_removed_user_email TO removed_user_email');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE user_email TO new_user_email');
        $this->addSql("ALTER TABLE user_email_old ADD CONSTRAINT `FK_550872CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)");
        $this->addSql('RENAME TABLE user_email_old TO user_email');
        $this->addSql('RENAME TABLE removed_user_email TO new_removed_user_email');
        $this->addSql("ALTER TABLE removed_user_email_old ADD CONSTRAINT `FK_2DE8C2D5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `removed_user` (`user_id`)");
        $this->addSql('RENAME TABLE removed_user_email_old TO removed_user_email');
    }
}
