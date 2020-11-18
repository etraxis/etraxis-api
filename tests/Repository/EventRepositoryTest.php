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

use eTraxis\Entity\Event;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\EventRepository
 */
class EventRepositoryTest extends WebTestCase
{
    private Contracts\EventRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Event::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(EventRepository::class, $this->repository);
    }
}
