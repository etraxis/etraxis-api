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

namespace eTraxis\Application\Command\Fields\HandlerTrait;

use eTraxis\Application\Command\Fields as Command;
use eTraxis\Application\Command\Fields\Handler\HandlerTrait\IssueHandlerTrait;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Application\Command\Fields\Handler\HandlerTrait\IssueHandlerTrait
 */
class IssueHandlerTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $manager;

    /**
     * @var \eTraxis\Repository\Contracts\FieldRepositoryInterface
     */
    private $repository;

    /**
     * @var IssueHandlerTrait
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);

        $this->handler = new class() {
            use IssueHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::ISSUE, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertTrue(true);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnsupportedCommand()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported command.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateCheckboxFieldCommand([
            'defaultValue' => true,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnsupportedFieldType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported field type.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}