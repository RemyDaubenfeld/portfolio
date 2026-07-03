<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703125235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chatbot_config (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, icon_name VARCHAR(255) DEFAULT NULL, intro_message1 LONGTEXT DEFAULT NULL, intro_message2 LONGTEXT DEFAULT NULL, rules LONGTEXT DEFAULT NULL, model VARCHAR(100) NOT NULL, temperature DOUBLE PRECISION NOT NULL, max_tokens INT NOT NULL, is_active TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chatbot_prompt (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(100) NOT NULL, context VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, position INT NOT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE chatbot_config');
        $this->addSql('DROP TABLE chatbot_prompt');
    }
}
