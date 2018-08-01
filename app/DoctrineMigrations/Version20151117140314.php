<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151117140314 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE remove_user_balance (list_id INT NOT NULL, platform VARCHAR(20) NOT NULL, balance NUMERIC(16, 4) NOT NULL, PRIMARY KEY(list_id, platform))");
        $this->addSql("UPDATE remove_user_list SET memo = 'ab取得額度失敗' WHERE memo = '歐博取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = 'ag取得額度失敗' WHERE memo = 'AG取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = 'sabah取得額度失敗' WHERE memo = '沙巴取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = 'mg取得額度失敗' WHERE memo = 'MG機率取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = 'og取得額度失敗' WHERE memo = '東方視訊取得額度失敗'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE remove_user_balance");
        $this->addSql("UPDATE remove_user_list SET memo = '歐博取得額度失敗' WHERE memo = 'ab取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = 'AG取得額度失敗' WHERE memo = 'ag取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = '沙巴取得額度失敗' WHERE memo = 'sabah取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = 'MG機率取得額度失敗' WHERE memo = 'mg取得額度失敗'");
        $this->addSql("UPDATE remove_user_list SET memo = '東方視訊取得額度失敗' WHERE memo = 'og取得額度失敗'");
    }
}
