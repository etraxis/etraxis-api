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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Projects\Handler\SuspendProjectHandler::__invoke
 */
class SuspendProjectCommandTest extends TransactionalTestCase
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

    public function testSuspendProject()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        static::assertFalse($project->isSuspended);

        $command = new SuspendProjectCommand([
            'project' => $project->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($project);
        static::assertTrue($project->isSuspended);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        static::assertTrue($project->isSuspended);

        $command = new SuspendProjectCommand([
            'project' => $project->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        $command = new SuspendProjectCommand([
            'project' => $project->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownProject()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SuspendProjectCommand([
            'project' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }
}
