<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Repository;

use eTraxis\Entity\State;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\StateRepository
 */
class StateRepositoryTest extends WebTestCase
{
    private Contracts\StateRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(StateRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        [$expected] = $this->repository->findBy(['name' => 'New']);
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }
}
