<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710071416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute page_key sur seo pour distinguer la config SEO par page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seo ADD page_key VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C71EC30462FA338 ON seo (page_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_6C71EC30462FA338 ON seo');
        $this->addSql('ALTER TABLE seo DROP page_key');
    }
}
