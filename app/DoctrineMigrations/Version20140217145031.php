<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140217145031 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE user_detail MODIFY id INT(11) NOT NULL");
        $this->addSql("ALTER TABLE user_detail DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE user_detail ADD PRIMARY KEY (user_id)");
        $this->addSql("ALTER TABLE user_detail DROP INDEX UNIQ_4B5464AEA76ED395");
        $this->addSql("ALTER TABLE removed_user_detail DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE removed_user_detail ADD PRIMARY KEY (user_id)");
        $this->addSql("ALTER TABLE removed_user_detail DROP INDEX UNIQ_8F1D9653A76ED395");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE UNIQUE INDEX UNIQ_4B5464AEA76ED395 ON user_detail (user_id)");
        $this->addSql("ALTER TABLE user_detail DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE user_detail ADD PRIMARY KEY (id)");
        $this->addSql("ALTER TABLE user_detail MODIFY id int(11) AUTO_INCREMENT NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_8F1D9653A76ED395 ON removed_user_detail (user_id)");
        $this->addSql("ALTER TABLE removed_user_detail DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE removed_user_detail ADD PRIMARY KEY (id)");
    }
}
