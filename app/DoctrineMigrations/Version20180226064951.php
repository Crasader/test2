<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180226064951 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE mlv FROM merchant_level_vendor mlv INNER JOIN merchant m ON mlv.merchant_id = m.id INNER JOIN payment_gateway_has_payment_vendor pv ON m.payment_gateway_id = pv.payment_gateway_id INNER JOIN payment_vendor p ON p.id = pv.payment_vendor_id WHERE pv.payment_gateway_id = 8 AND p.payment_method_id IN (3, 4, 5)');
        $this->addSql('DELETE mlm FROM merchant_level_method mlm INNER JOIN merchant m ON mlm.merchant_id = m.id INNER JOIN payment_gateway_has_payment_method p ON m.payment_gateway_id = p.payment_gateway_id WHERE p.payment_gateway_id = 8 AND p.payment_method_id IN (3, 4, 5)');
        $this->addSql('DELETE p from payment_gateway_has_payment_vendor p INNER JOIN payment_vendor v ON p.payment_vendor_id = v.id WHERE payment_gateway_id = 8 AND payment_method_id IN (3, 4, 5)');
        $this->addSql('DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 8 AND payment_method_id IN (3, 4, 5)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO payment_gateway_has_payment_method VALUES (8, 3), (8, 4), (8, 5)');
        $this->addSql('INSERT INTO payment_gateway_has_payment_vendor VALUES (8, 1003), (8, 1004), (8, 1005), (8, 1006), (8, 1007), (8, 1008), (8, 1009), (8, 1010), (8, 1011), (8, 1012), (8, 1013), (8, 1014), (8, 1015), (8, 1016), (8, 1017), (8, 1018), (8, 1019), (8, 1020), (8, 1021), (8, 1022), (8, 1023), (8, 1024), (8, 1027), (8, 1028), (8, 1029), (8, 1030), (8, 1031), (8, 1032), (8, 1033), (8, 1034), (8, 1035), (8, 1036), (8, 1037), (8, 1038), (8, 1039), (8, 1040), (8, 1041), (8, 1042), (8, 1043), (8, 1044), (8, 1045), (8, 1046), (8, 1047), (8, 1048), (8, 1051), (8, 1052), (8, 1053), (8, 1054), (8, 1055), (8, 1056), (8, 1057), (8, 1058), (8, 1059), (8, 1060), (8, 1061), (8, 1062), (8, 1063), (8, 1064), (8, 1065), (8, 1066), (8, 1067), (8, 1068), (8, 1069), (8, 1070), (8, 1071), (8, 1072)');
    }
}
