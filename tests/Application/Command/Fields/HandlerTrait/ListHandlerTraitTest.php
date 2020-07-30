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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Fields as Command;
use eTraxis\Application\Command\Fields\Handler\HandlerTrait\ListHandlerTrait;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Application\Command\Fields\Handler\HandlerTrait\ListHandlerTrait
 */
class ListHandlerTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private TranslatorInterface      $translator;
    private EntityManagerInterface   $manager;
    private FieldRepositoryInterface $repository;
    private object                   $handler;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);

        $this->handler = new class() {
            use ListHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::LIST, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var \eTraxis\Entity\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($this->manager);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(2, $facade->getDefaultValue()->value);

        $command = new Command\UpdateListFieldCommand([
            'default' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame(1, $facade->getDefaultValue()->value);

        $command = new Command\UpdateListFieldCommand([
            'default' => null,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertNull($facade->getDefaultValue());
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnknownItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new Command\UpdateListFieldCommand([
            'default' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldWrongItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 2], ['id' => 'DESC']);

        $command = new Command\UpdateListFieldCommand([
            'default' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnsupportedCommand()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported command.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnsupportedFieldType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported field type.');

        /** @var \eTraxis\Repository\Contracts\ListItemRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 2], ['id' => 'ASC']);

        $command = new Command\UpdateListFieldCommand([
            'default' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
