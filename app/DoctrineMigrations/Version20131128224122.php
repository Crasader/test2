<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131128224122 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE oauth_user_binding (id INT UNSIGNED AUTO_INCREMENT NOT NULL, oauth_vendor_id INT UNSIGNED NOT NULL, user_id INT NOT NULL, openid VARCHAR(50) NOT NULL, INDEX IDX_4A0372E3E222CEBF (oauth_vendor_id), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE oauth_vendor (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(10) NOT NULL, api_url VARCHAR(20) NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE oauth (id INT UNSIGNED AUTO_INCREMENT NOT NULL, oauth_vendor_id INT UNSIGNED NOT NULL, domain INT NOT NULL, app_id VARCHAR(100) NOT NULL, app_key VARCHAR(100) NOT NULL, redirect_url VARCHAR(100) NOT NULL, INDEX IDX_4DA78C4E222CEBF (oauth_vendor_id), PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE oauth_user_binding ADD CONSTRAINT FK_4A0372E3E222CEBF FOREIGN KEY (oauth_vendor_id) REFERENCES oauth_vendor (id)");
        $this->addSql("ALTER TABLE oauth ADD CONSTRAINT FK_4DA78C4E222CEBF FOREIGN KEY (oauth_vendor_id) REFERENCES oauth_vendor (id)");
        $this->addSql("INSERT INTO oauth_vendor (id, name, api_url) VALUES (1, 'weibo', 'api.weibo.com')");
        $this->addSql("INSERT INTO oauth_vendor (id, name, api_url) VALUES (2, 'qq', 'graph.qq.com')");
        $this->addSql("INSERT INTO oauth_vendor (id, name, api_url) VALUES (3, 'facebook', 'graph.facebook.com')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE oauth_user_binding DROP FOREIGN KEY FK_4A0372E3E222CEBF");
        $this->addSql("ALTER TABLE oauth DROP FOREIGN KEY FK_4DA78C4E222CEBF");
        $this->addSql("DROP TABLE oauth_vendor");
        $this->addSql("DROP TABLE oauth");
        $this->addSql("DROP TABLE oauth_user_binding");
    }
}
