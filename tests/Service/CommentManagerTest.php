<?php

namespace App\Tests\Service;

use App\Entity\Comments;
use App\Service\CommentManager;
use PHPUnit\Framework\TestCase;

/**
 * CommentManagerTest — Unit tests for the CommentManager business service.
 *
 * Business rules tested:
 *  1. A comment's content is mandatory (cannot be empty).
 *  2. Upvote count cannot be negative.
 *  3. Downvote count cannot be negative.
 *  4. A comment must be linked to a valid post (postId > 0).
 */
class CommentManagerTest extends TestCase
{
    // =========================================================
    // Rule 1 — Content is mandatory
    // =========================================================

    public function testValidCommentPassesValidation(): void
    {
        $comment = new Comments();
        $comment->setContent('This is a great post!');
        $comment->setUpvotes(5);
        $comment->setDownvotes(0);
        $comment->setPostId(1);

        $manager = new CommentManager();

        $this->assertTrue($manager->validate($comment));
    }

    public function testCommentWithEmptyContentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The comment content is mandatory and cannot be empty.');

        $comment = new Comments();
        $comment->setContent('');
        $comment->setUpvotes(0);
        $comment->setDownvotes(0);
        $comment->setPostId(1);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    public function testCommentWithOnlyWhitespaceContentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $comment = new Comments();
        $comment->setContent('     ');
        $comment->setUpvotes(0);
        $comment->setDownvotes(0);
        $comment->setPostId(1);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    // =========================================================
    // Rule 2 — Upvote count cannot be negative
    // =========================================================

    public function testCommentWithNegativeUpvotesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The upvote count of a comment cannot be negative.');

        $comment = new Comments();
        $comment->setContent('A valid comment.');
        $comment->setUpvotes(-1);
        $comment->setDownvotes(0);
        $comment->setPostId(1);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    public function testCommentWithZeroUpvotesPassesValidation(): void
    {
        $comment = new Comments();
        $comment->setContent('A valid comment.');
        $comment->setUpvotes(0);
        $comment->setDownvotes(0);
        $comment->setPostId(1);

        $manager = new CommentManager();

        $this->assertTrue($manager->validate($comment));
    }

    // =========================================================
    // Rule 3 — Downvote count cannot be negative
    // =========================================================

    public function testCommentWithNegativeDownvotesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The downvote count of a comment cannot be negative.');

        $comment = new Comments();
        $comment->setContent('A valid comment.');
        $comment->setUpvotes(0);
        $comment->setDownvotes(-3);
        $comment->setPostId(1);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    // =========================================================
    // Rule 4 — Comment must be linked to a valid post
    // =========================================================

    public function testCommentWithNullPostIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A comment must be linked to a valid post (postId must be greater than 0).');

        $comment = new Comments();
        $comment->setContent('A valid comment.');
        $comment->setUpvotes(0);
        $comment->setDownvotes(0);
        $comment->setPostId(null);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    public function testCommentWithZeroPostIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $comment = new Comments();
        $comment->setContent('A valid comment.');
        $comment->setUpvotes(0);
        $comment->setDownvotes(0);
        $comment->setPostId(0);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    public function testCommentWithValidPostIdPassesValidation(): void
    {
        $comment = new Comments();
        $comment->setContent('A valid comment.');
        $comment->setUpvotes(10);
        $comment->setDownvotes(2);
        $comment->setPostId(42);

        $manager = new CommentManager();

        $this->assertTrue($manager->validate($comment));
    }
}
