<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160204151327 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('remove-demo-user', 1, '2016-01-01 00:00:00', '2016-01-01 00:00:00', '2016-01-01 00:00:00', '刪除試玩站過期帳號', 0, 0)");
        $this->addSql("UPDATE `background_process` SET `memo` = '新增刪除計畫使用者名單' WHERE `name` = 'create-remove-user-list'");
        $this->addSql("UPDATE `background_process` SET `memo` = '刪除整合及大球站刪除計畫使用者' WHERE `name` = 'remove-user'");
        $this->addSql("UPDATE `background_process` SET `memo` = '刪除大小球站停用過期使用者, 每天2:30' WHERE `name` = 'remove-overdue-user'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM background_process WHERE name = 'remove-demo-user'");
        $this->addSql("UPDATE `background_process` SET `memo` = '新增刪除使用者名單' WHERE `name` = 'create-remove-user-list'");
        $this->addSql("UPDATE `background_process` SET `memo` = '刪除使用者' WHERE `name` = 'remove-user'");
        $this->addSql("UPDATE `background_process` SET `memo` = '刪除過期會員, 每天2:30' WHERE `name` = 'remove-overdue-user'");
    }
}
