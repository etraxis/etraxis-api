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

namespace eTraxis\Application\Command\Projects;

use eTraxis\Entity\Project;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @covers \eTraxis\Application\Command\Projects\Handler\CreateProjectHandler::__invoke
 */
class CreateProjectCommandTest extends TransactionalTestCase
{
    private ProjectRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Awesome Express']);
        self::assertNull($project);

        $command = new CreateProjectCommand([
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Awesome Express']);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame($result, $project);

        self::assertSame('Awesome Express', $project->name);
        self::assertSame('Newspaper-delivery company', $project->description);
        self::assertTrue($project->isSuspended);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $command = new CreateProjectCommand([
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ]);

        $this->commandBus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Project with specified name already exists.');

        $this->loginAs('admin@example.com');

        $command = new CreateProjectCommand([
            'name'        => 'Distinctio',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ]);

        $this->commandBus->handle($command);
    }
}
