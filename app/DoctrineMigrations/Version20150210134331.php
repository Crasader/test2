<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150210134331 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_trans DROP FOREIGN KEY FK_431F85913D7A0C28");
        $this->addSql("DROP INDEX IDX_431F85913D7A0C28 ON cash_trans");
        $this->addSql("ALTER TABLE cash_trans CHANGE cash_id cash_id INT NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_trans DROP FOREIGN KEY FK_DF510C819354354D");
        $this->addSql("DROP INDEX IDX_DF510C819354354D ON cash_fake_trans");
        $this->addSql("ALTER TABLE cash_fake_trans CHANGE cash_fake_id cash_fake_id INT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_fake_trans CHANGE cash_fake_id cash_fake_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE cash_fake_trans ADD CONSTRAINT FK_DF510C819354354D FOREIGN KEY (cash_fake_id) REFERENCES cash_fake (id)");
        $this->addSql("CREATE INDEX IDX_DF510C819354354D ON cash_fake_trans (cash_fake_id)");
        $this->addSql("ALTER TABLE cash_trans CHANGE cash_id cash_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE cash_trans ADD CONSTRAINT FK_431F85913D7A0C28 FOREIGN KEY (cash_id) REFERENCES cash (id)");
        $this->addSql("CREATE INDEX IDX_431F85913D7A0C28 ON cash_trans (cash_id)");
    }
}
