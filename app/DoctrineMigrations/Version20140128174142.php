<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140128174142 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        //轉換資料
        $this->addSql("ALTER TABLE removed_cash ADD user_id INT(11) NOT NULL AFTER removed_user_id");
        $this->addSql("UPDATE removed_cash rc INNER JOIN removed_user ru ON rc.removed_user_id = ru.id SET rc.user_id = ru.origin_id");
        $this->addSql("ALTER TABLE removed_user_detail ADD user_id INT(11) NOT NULL AFTER removed_user_id");
        $this->addSql("UPDATE removed_user_detail rud INNER JOIN removed_user ru ON rud.removed_user_id = ru.id SET rud.user_id = ru.origin_id");

        //移除欄位
        $this->addSql("ALTER TABLE removed_cash DROP FOREIGN KEY FK_888EF66DF4BCAA6A");
        $this->addSql("ALTER TABLE removed_cash DROP INDEX IDX_888EF66DF4BCAA6A");
        $this->addSql("ALTER TABLE removed_cash DROP COLUMN removed_user_id");
        $this->addSql("ALTER TABLE removed_user_detail DROP FOREIGN KEY FK_8F1D9653F4BCAA6A");
        $this->addSql("ALTER TABLE removed_user_detail DROP INDEX UNIQ_8F1D9653F4BCAA6A");
        $this->addSql("ALTER TABLE removed_user_detail DROP COLUMN removed_user_id");

        //更改主鍵
        $this->addSql("ALTER TABLE removed_user MODIFY id INT(11) NOT NULL");
        $this->addSql("ALTER TABLE removed_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE removed_user DROP id");
        $this->addSql("ALTER TABLE removed_user CHANGE COLUMN origin_id user_id INT(11) NOT NULL");
        $this->addSql("ALTER TABLE removed_user ADD PRIMARY KEY (user_id)");
        $this->addSql("ALTER TABLE removed_user DROP INDEX idx_removed_user_origin_id");

        //加入外鍵及索引
        $this->addSql("ALTER TABLE removed_cash ADD CONSTRAINT FK_888EF66DA76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)");
        $this->addSql("CREATE INDEX IDX_888EF66DA76ED395 ON removed_cash (user_id)");
        $this->addSql("ALTER TABLE removed_user_detail ADD CONSTRAINT FK_8F1D9653A76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_8F1D9653A76ED395 ON removed_user_detail (user_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
    }
}
