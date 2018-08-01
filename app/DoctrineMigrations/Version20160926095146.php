<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160926095146 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 1 WHERE id = 101');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 2 WHERE id = 103');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 3 WHERE id = 64');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 4 WHERE id = 93');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 5 WHERE id = 116');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 6 WHERE id = 70');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 7 WHERE id = 119');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 8 WHERE id = 41');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 9 WHERE id = 100');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 10 WHERE id = 27');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 11 WHERE id = 122');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 12 WHERE id = 97');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 13 WHERE id = 104');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 14 WHERE id = 88');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 15 WHERE id = 115');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 16 WHERE id = 110');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 17 WHERE id = 118');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 18 WHERE id = 1');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 19 WHERE id = 89');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 20 WHERE id = 8');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 21 WHERE id = 90');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 22 WHERE id = 125');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 23 WHERE id = 120');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 24 WHERE id = 102');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 25 WHERE id = 92');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 26 WHERE id = 108');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 27 WHERE id = 71');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 29 WHERE id = 50');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 30 WHERE id = 65');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 31 WHERE id = 85');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 32 WHERE id = 124');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 33 WHERE id = 126');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 1 WHERE id = 34');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 2 WHERE id = 61');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 3 WHERE id = 121');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 4 WHERE id = 39');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 5 WHERE id = 91');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 6 WHERE id = 6');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 7 WHERE id = 74');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 8 WHERE id = 111');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 9 WHERE id = 4');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 10 WHERE id = 5');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 11 WHERE id = 12');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 12 WHERE id = 16');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 13 WHERE id = 18');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 14 WHERE id = 21');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 15 WHERE id = 22');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 16 WHERE id = 23');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 17 WHERE id = 24');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 18 WHERE id = 31');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 19 WHERE id = 32');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 20 WHERE id = 33');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 21 WHERE id = 36');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 22 WHERE id = 37');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 23 WHERE id = 38');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 24 WHERE id = 40');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 25 WHERE id = 42');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 26 WHERE id = 45');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 27 WHERE id = 47');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 28 WHERE id = 48');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 29 WHERE id = 49');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 30 WHERE id = 51');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 31 WHERE id = 52');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 32 WHERE id = 53');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 33 WHERE id = 54');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 34 WHERE id = 55');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 35 WHERE id = 56');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 36 WHERE id = 58');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 37 WHERE id = 59');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 38 WHERE id = 60');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 39 WHERE id = 62');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 40 WHERE id = 63');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 41 WHERE id = 67');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 42 WHERE id = 68');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 43 WHERE id = 69');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 44 WHERE id = 72');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 45 WHERE id = 75');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 46 WHERE id = 76');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 47 WHERE id = 77');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 48 WHERE id = 78');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 49 WHERE id = 79');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 50 WHERE id = 80');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 51 WHERE id = 81');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 52 WHERE id = 82');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 53 WHERE id = 83');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 54 WHERE id = 84');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 55 WHERE id = 86');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 56 WHERE id = 87');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 57 WHERE id = 94');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 58 WHERE id = 95');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 59 WHERE id = 96');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 60 WHERE id = 98');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 61 WHERE id = 99');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 62 WHERE id = 105');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 63 WHERE id = 106');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 64 WHERE id = 107');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 65 WHERE id = 109');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 66 WHERE id = 112');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 67 WHERE id = 114');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 68 WHERE id = 117');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 69 WHERE id = 123');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 1 WHERE id = 70');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 2 WHERE id = 41');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 3 WHERE id = 119');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 4 WHERE id = 91');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 5 WHERE id = 8');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 6 WHERE id = 90');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 7 WHERE id = 64');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 8 WHERE id = 101');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 9 WHERE id = 103');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 10 WHERE id = 27');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 11 WHERE id = 93');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 12 WHERE id = 89');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 13 WHERE id = 34');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 14 WHERE id = 1');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 15 WHERE id = 50');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 16 WHERE id = 96');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 17 WHERE id = 94');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 18 WHERE id = 71');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 19 WHERE id = 65');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 20 WHERE id = 88');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 21 WHERE id = 115');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 22 WHERE id = 104');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 23 WHERE id = 109');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 24 WHERE id = 110');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 25 WHERE id = 102');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 26 WHERE id = 116');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 27 WHERE id = 117');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 28 WHERE id = 118');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 29 WHERE id = 120');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 30 WHERE id = 121');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 31 WHERE id = 122');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 32 WHERE id = 123');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 33 WHERE id = 124');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 34 WHERE id = 125');
        $this->addSql('UPDATE payment_gateway SET hot = 1, order_id = 35 WHERE id = 126');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 1 WHERE id = 85');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 2 WHERE id = 75');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 3 WHERE id = 95');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 4 WHERE id = 97');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 5 WHERE id = 18');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 6 WHERE id = 38');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 7 WHERE id = 54');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 8 WHERE id = 61');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 9 WHERE id = 92');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 10 WHERE id = 74');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 11 WHERE id = 62');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 12 WHERE id = 79');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 13 WHERE id = 39');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 14 WHERE id = 87');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 15 WHERE id = 72');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 16 WHERE id = 5');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 17 WHERE id = 98');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 18 WHERE id = 52');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 19 WHERE id = 4');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 20 WHERE id = 68');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 21 WHERE id = 40');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 22 WHERE id = 86');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 23 WHERE id = 84');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 24 WHERE id = 82');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 25 WHERE id = 49');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 26 WHERE id = 24');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 27 WHERE id = 81');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 28 WHERE id = 47');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 29 WHERE id = 33');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 30 WHERE id = 83');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 31 WHERE id = 56');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 32 WHERE id = 78');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 33 WHERE id = 100');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 34 WHERE id = 99');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 35 WHERE id = 80');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 36 WHERE id = 77');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 37 WHERE id = 76');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 38 WHERE id = 69');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 39 WHERE id = 67');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 40 WHERE id = 63');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 41 WHERE id = 60');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 42 WHERE id = 59');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 43 WHERE id = 58');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 44 WHERE id = 55');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 45 WHERE id = 53');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 46 WHERE id = 51');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 47 WHERE id = 48');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 48 WHERE id = 45');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 49 WHERE id = 42');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 50 WHERE id = 37');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 51 WHERE id = 36');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 52 WHERE id = 32');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 53 WHERE id = 31');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 54 WHERE id = 23');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 55 WHERE id = 22');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 56 WHERE id = 21');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 57 WHERE id = 16');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 58 WHERE id = 12');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 59 WHERE id = 105');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 60 WHERE id = 106');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 61 WHERE id = 108');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 62 WHERE id = 111');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 63 WHERE id = 114');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 64 WHERE id = 107');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 65 WHERE id = 6');
        $this->addSql('UPDATE payment_gateway SET hot = 0, order_id = 66 WHERE id = 112');
    }
}
