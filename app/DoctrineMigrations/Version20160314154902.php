<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160314154902 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reward (id INT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, quantity INT NOT NULL, min_amount NUMERIC(16, 4) NOT NULL, max_amount NUMERIC(16, 4) NOT NULL, begin_at DATETIME NOT NULL, end_at DATETIME NOT NULL, created_at DATETIME NOT NULL, obtain_amount NUMERIC(16, 4) NOT NULL, obtain_quantity INT NOT NULL, entry_created TINYINT(1) DEFAULT 0 NOT NULL, memo VARCHAR(100) DEFAULT \'\' NOT NULL, INDEX idx_reward_domain (domain), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reward_entry (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, reward_id INT UNSIGNED NOT NULL, user_id INT DEFAULT NULL, amount NUMERIC(16, 4) NOT NULL, created_at DATETIME NOT NULL, obtain_at DATETIME DEFAULT NULL, payoff_at DATETIME DEFAULT NULL, INDEX idx_reward_entry_user_id (user_id), INDEX idx_reward_entry_reward_id (reward_id), PRIMARY KEY(id))');
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('create-reward-entry', '1', '2016-01-01 00:00:00', '2016-01-01 00:00:00', NULL, '建立紅包明細,20分鐘', '0', '0')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE reward_entry');
        $this->addSql('DROP TABLE reward');
        $this->addSql("DELETE FROM background_process WHERE name = 'create-reward-entry'");
    }
}
