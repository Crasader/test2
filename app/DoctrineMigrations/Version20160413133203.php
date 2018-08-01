<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160413133203 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remove_plan_user DROP INDEX idx_remove_plan_user_plan_id, ADD INDEX idx_rm_plan_user_plan_id(plan_id)');
        $this->addSql('ALTER TABLE remove_plan_user RENAME rm_plan_user');
        $this->addSql('ALTER TABLE remove_plan RENAME rm_plan');
        $this->addSql('ALTER TABLE remove_plan_user_extra_balance CHANGE plan_user_id id INT NOT NULL');
        $this->addSql('ALTER TABLE remove_plan_user_extra_balance RENAME rm_plan_user_extra_balance');
        $this->addSql('ALTER TABLE remove_plan_queue RENAME rm_plan_queue');
        $this->addSql('ALTER TABLE remove_plan_level DROP INDEX idx_remove_plan_level_level_id, ADD INDEX idx_rm_plan_level_level_id(level_id)');
        $this->addSql('ALTER TABLE remove_plan_level RENAME rm_plan_level');
        $this->addSql("UPDATE background_process SET name = 'sync-remove-plan-user' WHERE name = 'create-remove-plan-user'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rm_plan_user DROP INDEX idx_rm_plan_user_plan_id, ADD INDEX idx_remove_plan_user_plan_id(plan_id)');
        $this->addSql('ALTER TABLE rm_plan_user RENAME remove_plan_user');
        $this->addSql('ALTER TABLE rm_plan RENAME remove_plan');
        $this->addSql('ALTER TABLE rm_plan_user_extra_balance CHANGE id plan_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE rm_plan_user_extra_balance RENAME remove_plan_user_extra_balance');
        $this->addSql('ALTER TABLE rm_plan_queue RENAME remove_plan_queue');
        $this->addSql('ALTER TABLE rm_plan_level DROP INDEX idx_rm_plan_level_level_id, ADD INDEX idx_remove_plan_level_level_id(level_id)');
        $this->addSql('ALTER TABLE rm_plan_level RENAME remove_plan_level');
        $this->addSql("UPDATE background_process SET name = 'create-remove-plan-user' WHERE name = 'sync-remove-plan-user'");
    }
}
