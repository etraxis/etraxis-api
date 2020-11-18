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

namespace eTraxis\Application\Command\Templates;

use eTraxis\Entity\Project;
use eTraxis\Entity\Template;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Templates\Handler\CreateTemplateHandler::__invoke
 */
class CreateTemplateCommandTest extends TransactionalTestCase
{
    private TemplateRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['name' => 'Bugfix']);
        self::assertNull($template);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['name' => 'Bugfix']);
        self::assertInstanceOf(Template::class, $template);
        self::assertSame($result, $template);

        self::assertSame($project, $template->project);
        self::assertSame('Bugfix', $template->name);
        self::assertSame('bug', $template->prefix);
        self::assertSame('Error reports', $template->description);
        self::assertSame(5, $template->criticalAge);
        self::assertSame(10, $template->frozenTime);
        self::assertTrue($template->isLocked);
    }

    public function testUnknownProject()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new CreateTemplateCommand([
            'project'     => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Development',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testPrefixConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified prefix already exists.');

        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'task',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }
}
