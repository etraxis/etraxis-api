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

use eTraxis\Entity\Project;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\ProjectRepository
 */
class ProjectRepositoryTest extends WebTestCase
{
    private Contracts\ProjectRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        static::assertInstanceOf(ProjectRepository::class, $this->repository);
    }
}
