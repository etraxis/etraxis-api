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

/**
 * @covers \eTraxis\Application\Command\Templates\Handler\DeleteTemplateHandler::__invoke
 */
class DeleteTemplateCommandTest extends TransactionalTestCase
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
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);
        self::assertNotNull($template);

        $command = new DeleteTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $template = $this->repository->find($command->template);
        self::assertNull($template);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteTemplateCommand([
            'template' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $command = new DeleteTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);
    }
}
