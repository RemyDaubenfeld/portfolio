<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615125118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill DROP is_main, CHANGE category_id category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47712469DE2 FOREIGN KEY (category_id) REFERENCES skill_category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE47712469DE2');
        $this->addSql('ALTER TABLE skill ADD is_main INT DEFAULT 0 NOT NULL, CHANGE category_id category_id INT NOT NULL');
    }
}
