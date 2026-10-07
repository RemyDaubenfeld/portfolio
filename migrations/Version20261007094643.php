<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Départements de recherche et suppression des paramètres hérités.
 */
final class Version20261007094643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Job search : départements ciblés par n8n gérés dans l\'admin, suppression de la table setting';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(3) NOT NULL, name VARCHAR(100) NOT NULL, active TINYINT NOT NULL, UNIQUE INDEX UNIQ_CD1DE18A77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        // Les 3 départements jusque-là envoyés à n8n, plus le reste de la zone (inactifs)
        $this->addSql("INSERT INTO department (code, name, active) VALUES
            ('54', 'Meurthe-et-Moselle', 1),
            ('55', 'Meuse', 1),
            ('57', 'Moselle', 1),
            ('67', 'Bas-Rhin', 0),
            ('68', 'Haut-Rhin', 0),
            ('88', 'Vosges', 0)");

        // Paramètres de l'ancien formulaire de contact, plus utilisés
        $this->addSql('DROP TABLE setting');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE setting (setting_key VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, value LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, PRIMARY KEY (setting_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE department');
    }
}
