<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617145017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD hero_tagline LONGTEXT DEFAULT NULL, CHANGE cv_file cv_file VARCHAR(255) DEFAULT \'CV_DAUBENFELD_Rémy.pdf\', CHANGE photo_file photo_file VARCHAR(255) DEFAULT \'\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP hero_tagline, CHANGE photo_file photo_file VARCHAR(255) DEFAULT \'assets/img/portfolio/photo400px.webp\', CHANGE cv_file cv_file VARCHAR(255) DEFAULT \'dl/CV_DAUBENFELD_Rémy.pdf\'');
    }
}
