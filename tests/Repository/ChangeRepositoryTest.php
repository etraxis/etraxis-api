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

use eTraxis\Entity\Change;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\ChangeRepository
 */
class ChangeRepositoryTest extends WebTestCase
{
    /**
     * @var Contracts\ChangeRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Change::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(ChangeRepository::class, $this->repository);
    }
}