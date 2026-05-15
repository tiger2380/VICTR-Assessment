<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table for GitHub repository data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE github_repository (
            github_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(512) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            pushed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            description LONGTEXT DEFAULT NULL,
            stars_count INT NOT NULL,
            PRIMARY KEY(github_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE github_repository');
    }
}
