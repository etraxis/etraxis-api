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

use eTraxis\Entity\LastRead;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\LastReadRepository
 */
class LastReadRepositoryTest extends TransactionalTestCase
{
    /**
     * @var Contracts\LastReadRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(LastRead::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(LastReadRepository::class, $this->repository);
    }
}
