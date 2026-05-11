<?php

namespace App\Service;

use App\Entity\ForumPosts;

/**
 * ForumPostManager — Business service for the ForumPosts entity.
 *
 * Business rules enforced:
 *  1. A post title is mandatory (cannot be empty).
 *  2. A post title must not exceed 255 characters.
 *  3. A post's view count cannot be negative.
 *  4. A locked post cannot have its content modified.
 */
class ForumPostManager
{
    /**
     * Validates a ForumPosts object against all defined business rules.
     *
     * @throws \InvalidArgumentException when any business rule is violated.
     */
    public function validate(ForumPosts $post): bool
    {
        // Rule 1: Title is mandatory
        if (empty(trim((string) $post->getTitle()))) {
            throw new \InvalidArgumentException('The post title is mandatory and cannot be empty.');
        }

        // Rule 2: Title must not exceed 255 characters
        if (mb_strlen((string) $post->getTitle()) > 255) {
            throw new \InvalidArgumentException('The post title must not exceed 255 characters.');
        }

        // Rule 3: View count cannot be negative
        if ($post->getViews() < 0) {
            throw new \InvalidArgumentException('The view count of a post cannot be negative.');
        }

        return true;
    }

    /**
     * Updates the content of a post.
     * Rule 4: Content cannot be changed if comments are locked.
     *
     * @throws \InvalidArgumentException when the post has comments locked.
     */
    public function updateContent(ForumPosts $post, string $newContent): void
    {
        if ($post->getIsCommentsLocked()) {
            throw new \InvalidArgumentException('Cannot modify content: the post has its comments locked.');
        }

        $post->setContent($newContent);
    }
}
