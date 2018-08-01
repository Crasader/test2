<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140326122320 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE user_detail ADD passport VARCHAR(18) NOT NULL AFTER identity_code, ADD identity_card VARCHAR(18) NOT NULL AFTER passport, ADD driver_license VARCHAR(18) NOT NULL AFTER identity_card, ADD insurance_card VARCHAR(18) NOT NULL AFTER driver_license, ADD health_card VARCHAR(18) NOT NULL AFTER insurance_card");
        $this->addSql("CREATE INDEX idx_user_detail_passport ON user_detail (passport)");
        $this->addSql("CREATE INDEX idx_user_detail_identity_card ON user_detail (identity_card)");
        $this->addSql("UPDATE user_detail SET passport = identity_code WHERE identity_type = 'pp'");
        $this->addSql("UPDATE user_detail SET identity_card = identity_code WHERE identity_type = 'id'");
        $this->addSql("DROP INDEX idx_user_detail_identity_code ON user_detail");
        $this->addSql("ALTER TABLE removed_user_detail ADD passport VARCHAR(18) NOT NULL AFTER identity_code, ADD identity_card VARCHAR(18) NOT NULL AFTER passport, ADD driver_license VARCHAR(18) NOT NULL AFTER identity_card, ADD insurance_card VARCHAR(18) NOT NULL AFTER driver_license, ADD health_card VARCHAR(18) NOT NULL AFTER insurance_card");
        $this->addSql("UPDATE removed_user_detail SET passport = identity_code WHERE identity_type = 'pp'");
        $this->addSql("UPDATE removed_user_detail SET identity_card = identity_code WHERE identity_type = 'id'");
        $this->addSql("ALTER TABLE removed_user_detail DROP identity_type, DROP identity_code");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX idx_user_detail_identity_code ON user_detail (identity_code)");
        $this->addSql("UPDATE user_detail SET identity_type = 'pp', identity_code = passport WHERE passport != ''");
        $this->addSql("UPDATE user_detail SET identity_type = 'id', identity_code = identity_card WHERE identity_card != ''");
        $this->addSql("DROP INDEX idx_user_detail_passport ON user_detail");
        $this->addSql("DROP INDEX idx_user_detail_identity_card ON user_detail");
        $this->addSql("ALTER TABLE user_detail DROP passport, DROP identity_card, DROP driver_license, DROP insurance_card, DROP health_card");
        $this->addSql("ALTER TABLE removed_user_detail ADD identity_type VARCHAR(18) NOT NULL AFTER country, ADD identity_code VARCHAR(18) NOT NULL AFTER identity_type");
        $this->addSql("UPDATE removed_user_detail SET identity_type = 'pp', identity_code = passport WHERE passport != ''");
        $this->addSql("UPDATE removed_user_detail SET identity_type = 'id', identity_code = identity_card WHERE identity_card != ''");
        $this->addSql("ALTER TABLE removed_user_detail DROP passport, DROP identity_card, DROP driver_license, DROP insurance_card, DROP health_card");
    }
}
