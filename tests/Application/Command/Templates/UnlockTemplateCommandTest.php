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

namespace eTraxis\Application\Command\Templates;

use eTraxis\Entity\Template;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\Application\Command\Templates\Handler\UnlockTemplateHandler::__invoke
 */
class UnlockTemplateCommandTest extends TransactionalTestCase
{
    /**
     * @var \eTraxis\Repository\Contracts\TemplateRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testUnlockTemplate()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertTrue($template->isLocked);

        $command = new UnlockTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertFalse($template->isLocked);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        self::assertFalse($template->isLocked);

        $command = new UnlockTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $command = new UnlockTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UnlockTemplateCommand([
            'template' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }
}
