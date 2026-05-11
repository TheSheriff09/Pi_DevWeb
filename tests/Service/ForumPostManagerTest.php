<?php

namespace App\Tests\Service;

use App\Entity\ForumPosts;
use App\Service\ForumPostManager;
use PHPUnit\Framework\TestCase;

/**
 * ForumPostManagerTest — Unit tests for the ForumPostManager business service.
 *
 * Business rules tested:
 *  1. A post title is mandatory (cannot be empty).
 *  2. A post title must not exceed 255 characters.
 *  3. A post's view count cannot be negative.
 *  4. A locked post cannot have its content modified.
 */
class ForumPostManagerTest extends TestCase
{
    // =========================================================
    // Rule 1 — Title is mandatory
    // =========================================================

    public function testValidPostPassesValidation(): void
    {
        $post = new ForumPosts();
        $post->setTitle('How to start a startup?');
        $post->setContent('This is a detailed question about entrepreneurship.');
        $post->setViews(0);

        $manager = new ForumPostManager();

        $this->assertTrue($manager->validate($post));
    }

    public function testPostWithEmptyTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The post title is mandatory and cannot be empty.');

        $post = new ForumPosts();
        $post->setTitle('');
        $post->setContent('Some content here.');
        $post->setViews(0);

        $manager = new ForumPostManager();
        $manager->validate($post);
    }

    public function testPostWithWhitespaceTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $post = new ForumPosts();
        $post->setTitle('   ');
        $post->setContent('Some content here.');
        $post->setViews(0);

        $manager = new ForumPostManager();
        $manager->validate($post);
    }

    // =========================================================
    // Rule 2 — Title must not exceed 255 characters
    // =========================================================

    public function testPostWithTitleExceeding255CharactersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The post title must not exceed 255 characters.');

        $post = new ForumPosts();
        $post->setTitle(str_repeat('A', 256)); // 256 characters
        $post->setContent('Some content here.');
        $post->setViews(0);

        $manager = new ForumPostManager();
        $manager->validate($post);
    }

    public function testPostWithTitleExactly255CharactersPassesValidation(): void
    {
        $post = new ForumPosts();
        $post->setTitle(str_repeat('A', 255)); // exactly 255 characters
        $post->setContent('Some content here.');
        $post->setViews(0);

        $manager = new ForumPostManager();

        $this->assertTrue($manager->validate($post));
    }

    // =========================================================
    // Rule 3 — View count cannot be negative
    // =========================================================

    public function testPostWithNegativeViewsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The view count of a post cannot be negative.');

        $post = new ForumPosts();
        $post->setTitle('A valid title');
        $post->setContent('Some content here.');
        $post->setViews(-5);

        $manager = new ForumPostManager();
        $manager->validate($post);
    }

    public function testPostWithZeroViewsPassesValidation(): void
    {
        $post = new ForumPosts();
        $post->setTitle('A valid title');
        $post->setContent('Some content here.');
        $post->setViews(0);

        $manager = new ForumPostManager();

        $this->assertTrue($manager->validate($post));
    }

    // =========================================================
    // Rule 4 — Locked post cannot have its content modified
    // =========================================================

    public function testUpdatingContentOfLockedPostThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot modify content: the post has its comments locked.');

        $post = new ForumPosts();
        $post->setTitle('A locked post');
        $post->setContent('Original content.');
        $post->setIsCommentsLocked(true);

        $manager = new ForumPostManager();
        $manager->updateContent($post, 'Trying to update locked content.');
    }

    public function testUpdatingContentOfUnlockedPostSucceeds(): void
    {
        $post = new ForumPosts();
        $post->setTitle('An open post');
        $post->setContent('Original content.');
        $post->setIsCommentsLocked(false);

        $manager = new ForumPostManager();
        $manager->updateContent($post, 'Updated content.');

        $this->assertSame('Updated content.', $post->getContent());
    }
}
