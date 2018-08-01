<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180312020403 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = 391 AND mlv.`payment_vendor_id` IN ('8', '10', '11', '13', '15', '217', '218', '219', '220', '221', '222', '224', '226', '228', '229', '230', '302', '304', '305', '306', '307', '308', '309', '311', '312', '316', '317', '318', '321', '326', '328', '335', '337', '341', '342', '344', '345', '346', '347', '350', '352', '353', '355', '356', '357', '358', '359', '360', '361', '363', '364', '365', '368', '370', '371', '374', '381', '382', '383', '386', '392', '393', '406', '410', '411', '415', '421', '422', '424')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = 391 AND `payment_vendor_id` IN ('8', '10', '11', '13', '15', '217', '218', '219', '220', '221', '222', '224', '226', '228', '229', '230', '302', '304', '305', '306', '307', '308', '309', '311', '312', '316', '317', '318', '321', '326', '328', '335', '337', '341', '342', '344', '345', '346', '347', '350', '352', '353', '355', '356', '357', '358', '359', '360', '361', '363', '364', '365', '368', '370', '371', '374', '381', '382', '383', '386', '392', '393', '406', '410', '411', '415', '421', '422', '424')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('391', '8'), ('391', '10'), ('391', '11'), ('391', '13'), ('391', '15'), ('391', '217'), ('391', '218'), ('391', '219'), ('391', '220'), ('391', '221'), ('391', '222'), ('391', '224'), ('391', '226'), ('391', '228'), ('391', '229'), ('391', '230'), ('391', '302'), ('391', '304'), ('391', '305'), ('391', '306'), ('391', '307'), ('391', '308'), ('391', '309'), ('391', '311'), ('391', '312'), ('391', '316'), ('391', '317'), ('391', '318'), ('391', '321'), ('391', '326'), ('391', '328'), ('391', '335'), ('391', '337'), ('391', '341'), ('391', '342'), ('391', '344'), ('391', '345'), ('391', '346'), ('391', '347'), ('391', '350'), ('391', '352'), ('391', '353'), ('391', '355'), ('391', '356'), ('391', '357'), ('391', '358'), ('391', '359'), ('391', '360'), ('391', '361'), ('391', '363'), ('391', '364'), ('391', '365'), ('391', '368'), ('391', '370'), ('391', '371'), ('391', '374'), ('391', '381'), ('391', '382'), ('391', '383'), ('391', '386'), ('391', '392'), ('391', '393'), ('391', '406'), ('391', '410'), ('391', '411'), ('391', '415'), ('391', '421'), ('391', '422'), ('391', '424')");
    }
}
