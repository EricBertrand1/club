<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250920131625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE objet (idObjet INT AUTO_INCREMENT NOT NULL, titreObjet VARCHAR(255) NOT NULL, categorie VARCHAR(100) NOT NULL, imageObjet1 VARCHAR(255) DEFAULT NULL, imageObjet2 VARCHAR(255) DEFAULT NULL, imageObjet3 VARCHAR(255) DEFAULT NULL, auteur VARCHAR(100) NOT NULL, date DATE NOT NULL, prix INT NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(idObjet)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE objet');
    }
}
