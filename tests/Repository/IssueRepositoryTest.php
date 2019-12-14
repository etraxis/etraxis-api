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

use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\IssueRepository
 */
class IssueRepositoryTest extends TransactionalTestCase
{
    /**
     * @var Contracts\IssueRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(IssueRepository::class, $this->repository);
    }
}
