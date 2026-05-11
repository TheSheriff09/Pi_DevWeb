<?php

namespace App\Service;

use App\Entity\Comments;

/**
 * CommentManager — Business service for the Comments entity.
 *
 * Business rules enforced:
 *  1. A comment's content is mandatory (cannot be empty).
 *  2. Upvote count cannot be negative.
 *  3. Downvote count cannot be negative.
 *  4. A comment must be linked to a valid post (postId > 0).
 */
class CommentManager
{
    /**
     * Validates a Comments object against all defined business rules.
     *
     * @throws \InvalidArgumentException when any business rule is violated.
     */
    public function validate(Comments $comment): bool
    {
        // Rule 1: Content is mandatory
        if (empty(trim((string) $comment->getContent()))) {
            throw new \InvalidArgumentException('The comment content is mandatory and cannot be empty.');
        }

        // Rule 2: Upvote count cannot be negative
        if ($comment->getUpvotes() < 0) {
            throw new \InvalidArgumentException('The upvote count of a comment cannot be negative.');
        }

        // Rule 3: Downvote count cannot be negative
        if ($comment->getDownvotes() < 0) {
            throw new \InvalidArgumentException('The downvote count of a comment cannot be negative.');
        }

        // Rule 4: Comment must be linked to a valid post
        if ($comment->getPostId() === null || $comment->getPostId() <= 0) {
            throw new \InvalidArgumentException('A comment must be linked to a valid post (postId must be greater than 0).');
        }

        return true;
    }
}
