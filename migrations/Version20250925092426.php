<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250925092426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create project/task/task_user tables (idempotent)';
    }

    public function up(Schema $schema): void
    {
        // Si l’une des tables existe déjà, on saute la migration
        if ($schema->hasTable('project') || $schema->hasTable('task') || $schema->hasTable('task_user')) {
            $this->skipIf(true, 'Tables project/task/task_user déjà présentes — on saute la création.');
            return;
        }

        // NB: utilisez utf8mb4 si possible
        $this->addSql('CREATE TABLE project (
            id INT AUTO_INCREMENT NOT NULL,
            author_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_2FB3D0EEF675F31B (author_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE task (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            hours_planned INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            position SMALLINT UNSIGNED NOT NULL,
            INDEX IDX_527EDB25166D1F9C (project_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE task_user (
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            INDEX IDX_FE2042328DB60186 (task_id),
            INDEX IDX_FE204232A76ED395 (user_id),
            PRIMARY KEY(task_id, user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_user ADD CONSTRAINT FK_FE2042328DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_user ADD CONSTRAINT FK_FE204232A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Si rien n’existe, on saute
        if (!$schema->hasTable('project') && !$schema->hasTable('task') && !$schema->hasTable('task_user')) {
            $this->skipIf(true, 'Aucune table à supprimer.');
            return;
        }

        // Plus simple et robuste : on désactive les contraintes, on drop si existant
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS task_user');
        $this->addSql('DROP TABLE IF EXISTS task');
        $this->addSql('DROP TABLE IF EXISTS project');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
