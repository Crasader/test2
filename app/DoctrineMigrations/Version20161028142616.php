<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161028142616 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE removed_credit (id INT NOT NULL, user_id INT NOT NULL, group_num INT NOT NULL, INDEX IDX_7D2BA10CA76ED395 (user_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE removed_cash_fake (id INT NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, INDEX IDX_4AB65F3AA76ED395 (user_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE removed_card (id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_98238B07A76ED395 (user_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE removed_credit ADD CONSTRAINT FK_7D2BA10CA76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)');
        $this->addSql('ALTER TABLE removed_cash_fake ADD CONSTRAINT FK_4AB65F3AA76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)');
        $this->addSql('ALTER TABLE removed_card ADD CONSTRAINT FK_98238B07A76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE removed_credit');
        $this->addSql('DROP TABLE removed_cash_fake');
        $this->addSql('DROP TABLE removed_card');
    }
}
