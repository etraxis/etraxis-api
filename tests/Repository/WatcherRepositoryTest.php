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

use eTraxis\Entity\Watcher;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\WatcherRepository
 */
class WatcherRepositoryTest extends WebTestCase
{
    /**
     * @var Contracts\WatcherRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Watcher::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(WatcherRepository::class, $this->repository);
    }
}
