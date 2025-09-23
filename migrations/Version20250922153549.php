<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922153549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE castellum_preference (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, categories JSON NOT NULL, subcategories JSON NOT NULL, level VARCHAR(10) NOT NULL, count SMALLINT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_castellum_pref_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE castellum_preference ADD CONSTRAINT FK_A13C6D8CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE castellum_preference DROP FOREIGN KEY FK_A13C6D8CA76ED395');
        $this->addSql('DROP TABLE castellum_preference');
    }
}
