<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160411110448 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE remove_user_plan_queue (plan_id INT UNSIGNED NOT NULL, PRIMARY KEY(plan_id))');
        $this->addSql('ALTER TABLE remove_user_plan ADD queue_done TINYINT(1) NOT NULL');
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('generate-remove-user', 0, '2016-01-01 00:00:00', '2016-01-01 00:00:00', NULL, '產生準備要刪除的使用者佇列', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE remove_user_plan_queue');
        $this->addSql('ALTER TABLE remove_user_plan DROP queue_done');
        $this->addSql("DELETE FROM background_process WHERE name = 'generate-remove-user'");
    }
}
