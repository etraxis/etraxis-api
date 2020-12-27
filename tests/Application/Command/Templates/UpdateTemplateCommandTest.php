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

use eTraxis\Entity\Template;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Templates\Handler\UpdateTemplateHandler::__invoke
 */
class UpdateTemplateCommandTest extends TransactionalTestCase
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

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);

        /** @var Template $template */
        $template = $this->repository->find($template->id);

        static::assertSame('Bugfix', $template->name);
        static::assertSame('bug', $template->prefix);
        static::assertSame('Error reports', $template->description);
        static::assertSame(5, $template->criticalAge);
        static::assertSame(10, $template->frozenTime);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateTemplateCommand([
            'template'    => self::UNKNOWN_ENTITY_ID,
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

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Support',
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

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'req',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }
}
