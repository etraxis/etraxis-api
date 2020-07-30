<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Repository;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\UserRepository
 */
class UserRepositoryTest extends TransactionalTestCase
{
    private Contracts\UserRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(UserRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        /** @var User $expected */
        $expected = $this->repository->loadUserByUsername('admin@example.com');
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }

    /**
     * @covers ::loadUserByUsername
     */
    public function testLoadUserByUsernameSuccess()
    {
        /** @var User $user */
        $user = $this->repository->loadUserByUsername('admin@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('eTraxis Admin', $user->fullname);
    }

    /**
     * @covers ::loadUserByUsername
     */
    public function testLoadUserByUsernameUnknown()
    {
        /** @var User $user */
        $user = $this->repository->loadUserByUsername('404@example.com');

        self::assertNull($user);
    }

    /**
     * @covers ::upgradePassword
     */
    public function testUpgradePassword()
    {
        /** @var User $user */
        $user = $this->repository->loadUserByUsername('admin@example.com');

        $newPassword = md5('secret');

        self::assertNotSame($newPassword, $user->password);

        $this->repository->upgradePassword($user, $newPassword);

        self::assertSame($newPassword, $user->password);
    }
}
