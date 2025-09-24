<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924125755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE castellum_question DROP created_at, CHANGE level_question level_question VARCHAR(16) NOT NULL, CHANGE subject subject VARCHAR(255) DEFAULT NULL, CHANGE question_type question_type VARCHAR(50) DEFAULT NULL, CHANGE question_text question_text LONGTEXT DEFAULT NULL, CHANGE answer_text answer_text LONGTEXT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE castellum_question ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE level_question level_question VARCHAR(10) NOT NULL, CHANGE question_type question_type VARCHAR(50) NOT NULL, CHANGE subject subject VARCHAR(120) DEFAULT NULL, CHANGE question_text question_text LONGTEXT NOT NULL, CHANGE answer_text answer_text LONGTEXT NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
