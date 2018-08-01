<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170105163639 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_rm_plan_user_remove ON rm_plan_user');
        $this->addSql('DROP INDEX idx_rm_plan_user_cancel ON rm_plan_user');
        $this->addSql('DROP INDEX idx_rm_plan_user_recover_fail ON rm_plan_user');
        $this->addSql('DROP INDEX idx_rm_plan_user_get_balance_fail ON rm_plan_user');
        $this->addSql('CREATE INDEX idx_rm_plan_user_plan_id_timeout_count ON rm_plan_user (plan_id, timeout_count)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_rm_plan_user_plan_id_timeout_count ON rm_plan_user');
        $this->addSql('CREATE INDEX idx_rm_plan_user_remove ON rm_plan_user (remove)');
        $this->addSql('CREATE INDEX idx_rm_plan_user_cancel ON rm_plan_user (cancel)');
        $this->addSql('CREATE INDEX idx_rm_plan_user_recover_fail ON rm_plan_user (recover_fail)');
        $this->addSql('CREATE INDEX idx_rm_plan_user_get_balance_fail ON rm_plan_user (get_balance_fail)');
    }
}
