<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703065608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seo (id INT AUTO_INCREMENT NOT NULL, meta_title VARCHAR(160) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, robots_directive VARCHAR(50) DEFAULT NULL, canonical_url VARCHAR(255) DEFAULT NULL, og_title VARCHAR(160) DEFAULT NULL, og_description LONGTEXT DEFAULT NULL, og_image VARCHAR(255) DEFAULT NULL, json_ld LONGTEXT DEFAULT NULL, google_verification_token VARCHAR(255) DEFAULT NULL, sitemap_enabled TINYINT NOT NULL, robots_txt LONGTEXT DEFAULT NULL, llms_txt LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE seo');
    }
}
