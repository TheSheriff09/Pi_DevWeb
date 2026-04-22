<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422105801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `comment_reaction` (id INT AUTO_INCREMENT NOT NULL, comment_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `report` (id INT AUTO_INCREMENT NOT NULL, reporter_id INT NOT NULL, target_type VARCHAR(50) NOT NULL, target_id INT NOT NULL, reason LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE comments ADD upvotes INT DEFAULT 0 NOT NULL, ADD downvotes INT DEFAULT 0 NOT NULL, ADD is_edited TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE forum_posts ADD is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE users ADD gamification_points INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `comment_reaction`');
        $this->addSql('DROP TABLE `report`');
        $this->addSql('ALTER TABLE `comments` DROP upvotes, DROP downvotes, DROP is_edited');
        $this->addSql('ALTER TABLE `forum_posts` DROP is_active');
        $this->addSql('ALTER TABLE `users` DROP gamification_points');
    }
}
