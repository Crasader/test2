<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150116145212 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        // 刪除外來鍵
        $this->addSql("ALTER TABLE merchant_extra DROP FOREIGN KEY `FK_DAACD7226796D554`");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor DROP FOREIGN KEY `FK_5B2B8A046796D554`");
        $this->addSql("ALTER TABLE merchant_has_payment_method DROP FOREIGN KEY `FK_F05083926796D554`");
        $this->addSql("ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY `FK_EC6935F93616FF93`");
        $this->addSql("ALTER TABLE merchant_key DROP FOREIGN KEY `FK_8D25C0026796D554`");
        $this->addSql("ALTER TABLE merchant_stat DROP FOREIGN KEY `FK_68EF6FCC6796D554`");

        // 修改型態
        $this->addSql("ALTER TABLE merchant_extra MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_has_payment_method MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_ip_strategy MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_key MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_stat MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant MODIFY id INT UNSIGNED AUTO_INCREMENT NOT NULL");
        $this->addSql("ALTER TABLE merchant_payment_level MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_payment_level_method MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_payment_level_vendor MODIFY merchant_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE cash_deposit_entry MODIFY merchant_id INT UNSIGNED NOT NULL");

        // 還原外來鍵
        $this->addSql("ALTER TABLE merchant_extra ADD CONSTRAINT `FK_DAACD7226796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor ADD CONSTRAINT `FK_5B2B8A046796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_has_payment_method ADD CONSTRAINT `FK_F05083926796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_ip_strategy ADD CONSTRAINT `FK_EC6935F93616FF93` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_key ADD CONSTRAINT `FK_8D25C0026796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_stat ADD CONSTRAINT `FK_68EF6FCC6796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        // 刪除外來鍵
        $this->addSql("ALTER TABLE merchant_extra DROP FOREIGN KEY `FK_DAACD7226796D554`");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor DROP FOREIGN KEY `FK_5B2B8A046796D554`");
        $this->addSql("ALTER TABLE merchant_has_payment_method DROP FOREIGN KEY `FK_F05083926796D554`");
        $this->addSql("ALTER TABLE merchant_ip_strategy DROP FOREIGN KEY `FK_EC6935F93616FF93`");
        $this->addSql("ALTER TABLE merchant_key DROP FOREIGN KEY `FK_8D25C0026796D554`");
        $this->addSql("ALTER TABLE merchant_stat DROP FOREIGN KEY `FK_68EF6FCC6796D554`");

        // 修改型態
        $this->addSql("ALTER TABLE merchant_extra MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_has_payment_method MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_ip_strategy MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_key MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_stat MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant MODIFY id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL");
        $this->addSql("ALTER TABLE merchant_payment_level MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_payment_level_method MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE merchant_payment_level_vendor MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE cash_deposit_entry MODIFY merchant_id SMALLINT UNSIGNED NOT NULL");

        // 還原外來鍵
        $this->addSql("ALTER TABLE merchant_extra ADD CONSTRAINT `FK_DAACD7226796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor ADD CONSTRAINT `FK_5B2B8A046796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_has_payment_method ADD CONSTRAINT `FK_F05083926796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_ip_strategy ADD CONSTRAINT `FK_EC6935F93616FF93` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_key ADD CONSTRAINT `FK_8D25C0026796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
        $this->addSql("ALTER TABLE merchant_stat ADD CONSTRAINT `FK_68EF6FCC6796D554` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`)");
    }
}
