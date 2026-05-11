<?php

namespace App\Tests\Service;

use App\Entity\Users;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

/**
 * UserManagerTest — Unit tests for the UserManager business service.
 *
 * Business rules tested:
 *  1. The user's full name is mandatory.
 *  2. The email must be a valid email address.
 *  3. The role must be one of the allowed values.
 *  4. The password must be at least 8 characters long.
 */
class UserManagerTest extends TestCase
{
    // =========================================================
    // Rule 1 — Full name is mandatory
    // =========================================================

    public function testValidUserPassesValidation(): void
    {
        $user = new Users();
        $user->setFullName('Alice Martin');
        $user->setEmail('alice.martin@example.com');
        $user->setRole('ROLE_USER');

        $manager = new UserManager();

        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithEmptyFullNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The user\'s full name is mandatory and cannot be empty.');

        $user = new Users();
        $user->setFullName('');
        $user->setEmail('alice@example.com');
        $user->setRole('ROLE_USER');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithWhitespaceFullNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new Users();
        $user->setFullName('    ');
        $user->setEmail('alice@example.com');
        $user->setRole('ROLE_USER');

        $manager = new UserManager();
        $manager->validate($user);
    }

    // =========================================================
    // Rule 2 — Email must be valid
    // =========================================================

    public function testUserWithInvalidEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The email address is invalid.');

        $user = new Users();
        $user->setFullName('Bob Dupont');
        $user->setEmail('not-an-email');
        $user->setRole('ROLE_USER');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithEmailMissingAtSignThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new Users();
        $user->setFullName('Bob Dupont');
        $user->setEmail('invalidemail.com');
        $user->setRole('ROLE_USER');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithValidEmailPassesValidation(): void
    {
        $user = new Users();
        $user->setFullName('Carol Smith');
        $user->setEmail('carol.smith@company.org');
        $user->setRole('ROLE_MENTOR');

        $manager = new UserManager();

        $this->assertTrue($manager->validate($user));
    }

    // =========================================================
    // Rule 3 — Role must be one of the allowed values
    // =========================================================

    public function testUserWithInvalidRoleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new Users();
        $user->setFullName('Dave Brown');
        $user->setEmail('dave@example.com');
        $user->setRole('ROLE_HACKER');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithAllowedRoleAdminPassesValidation(): void
    {
        $user = new Users();
        $user->setFullName('Eve Admin');
        $user->setEmail('eve@example.com');
        $user->setRole('ROLE_ADMIN');

        $manager = new UserManager();

        $this->assertTrue($manager->validate($user));
    }

    // =========================================================
    // Rule 4 — Password must be at least 8 characters
    // =========================================================

    public function testShortPasswordThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be at least 8 characters long.');

        $manager = new UserManager();
        $manager->validateRawPassword('1234567'); // only 7 chars
    }

    public function testPasswordWithExactly8CharactersPassesValidation(): void
    {
        $manager = new UserManager();

        $this->assertTrue($manager->validateRawPassword('12345678')); // exactly 8 chars
    }

    public function testStrongPasswordPassesValidation(): void
    {
        $manager = new UserManager();

        $this->assertTrue($manager->validateRawPassword('Str0ng@Password!')); // 16 chars
    }
}
