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

use eTraxis\Entity\Group;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\GroupRepository
 */
class GroupRepositoryTest extends WebTestCase
{
    /**
     * @var Contracts\GroupRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(GroupRepository::class, $this->repository);
    }
}
