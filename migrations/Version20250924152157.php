<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924152157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coaching_theme ADD rubrique_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE coaching_theme ADD CONSTRAINT FK_674A15AE3BD38833 FOREIGN KEY (rubrique_id) REFERENCES rubrique (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_674A15AE3BD38833 ON coaching_theme (rubrique_id)');
        $this->addSql('ALTER TABLE rubrique CHANGE user_id user_id INT NOT NULL');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coaching_theme DROP FOREIGN KEY FK_674A15AE3BD38833');
        $this->addSql('DROP INDEX IDX_674A15AE3BD38833 ON coaching_theme');
        $this->addSql('ALTER TABLE coaching_theme DROP rubrique_id, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rubrique CHANGE user_id user_id INT DEFAULT NULL');
    }
}
