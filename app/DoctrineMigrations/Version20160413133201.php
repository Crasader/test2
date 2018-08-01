<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160413133201 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remove_user_list DROP INDEX idx_remove_user_list_plan_id, ADD INDEX idx_remove_plan_user_plan_id(plan_id)');
        $this->addSql('ALTER TABLE remove_user_list RENAME remove_plan_user');
        $this->addSql('ALTER TABLE remove_user_plan CHANGE list_created user_created TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE remove_user_plan RENAME remove_plan');
        $this->addSql('ALTER TABLE remove_user_extra_balance CHANGE list_id plan_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE remove_user_extra_balance RENAME remove_plan_user_extra_balance');
        $this->addSql('ALTER TABLE remove_user_plan_queue RENAME remove_plan_queue');
        $this->addSql('ALTER TABLE remove_user_level DROP INDEX idx_remove_user_level_level_id, ADD INDEX idx_remove_plan_level_level_id(level_id)');
        $this->addSql('ALTER TABLE remove_user_level RENAME remove_plan_level');
        $this->addSql("UPDATE background_process SET name = 'remove-plan-user' WHERE name = 'remove-user'");
        $this->addSql("UPDATE background_process SET name = 'create-remove-plan-user' WHERE name = 'create-remove-user-list'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remove_plan_user DROP INDEX idx_remove_plan_user_plan_id, ADD INDEX idx_remove_user_list_plan_id(plan_id)');
        $this->addSql('ALTER TABLE remove_plan_user RENAME remove_user_list');
        $this->addSql('ALTER TABLE remove_plan CHANGE user_created list_created TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE remove_plan RENAME remove_user_plan');
        $this->addSql('ALTER TABLE remove_plan_user_extra_balance CHANGE plan_user_id list_id INT NOT NULL');
        $this->addSql('ALTER TABLE remove_plan_user_extra_balance RENAME remove_user_extra_balance');
        $this->addSql('ALTER TABLE remove_plan_queue RENAME remove_user_plan_queue');
        $this->addSql('ALTER TABLE remove_plan_level DROP INDEX idx_remove_plan_level_level_id, ADD INDEX idx_remove_user_level_level_id(level_id)');
        $this->addSql('ALTER TABLE remove_plan_level RENAME remove_user_level');
        $this->addSql("UPDATE background_process SET name = 'remove-user' WHERE name = 'remove-plan-user'");
        $this->addSql("UPDATE background_process SET name = 'create-remove-user-list' WHERE name = 'create-remove-plan-user'");
    }
}
