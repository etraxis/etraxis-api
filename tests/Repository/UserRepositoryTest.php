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
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\UserRepository
 */
class UserRepositoryTest extends WebTestCase
{
    /**
     * @var Contracts\UserRepositoryInterface
     */
    private $repository;

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
     * @covers ::findOneByUsername
     */
    public function testFindOneByUsernameSuccess()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('admin@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('eTraxis Admin', $user->fullname);
    }

    /**
     * @covers ::findOneByUsername
     */
    public function testFindOneByUsernameUnknown()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('404@example.com');

        self::assertNull($user);
    }
}
