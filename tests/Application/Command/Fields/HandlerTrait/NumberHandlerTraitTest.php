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

namespace eTraxis\Application\Command\Fields\HandlerTrait;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Command\Fields as Command;
use eTraxis\Application\Command\Fields\Handler\HandlerTrait\NumberHandlerTrait;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Application\Command\Fields\Handler\HandlerTrait\NumberHandlerTrait
 */
class NumberHandlerTraitTest extends TransactionalTestCase
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
            use NumberHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::NUMBER, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        /** @var \eTraxis\Entity\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(0, $facade->getMinimumValue());
        self::assertSame(1000000000, $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new Command\UpdateNumberFieldCommand([
            'minimum' => -100000,
            'maximum' => +100000,
            'default' => 100,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame(-100000, $facade->getMinimumValue());
        self::assertSame(100000, $facade->getMaximumValue());
        self::assertSame(100, $facade->getDefaultValue());
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new Command\UpdateNumberFieldCommand([
            'minimum' => +100000,
            'maximum' => -100000,
            'default' => 100,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from -100000 to 100000.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new Command\UpdateNumberFieldCommand([
            'minimum' => -100000,
            'maximum' => +100000,
            'default' => 100001,
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
        [$field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

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

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateNumberFieldCommand([
            'minimum' => -100000,
            'maximum' => +100000,
            'default' => 100,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
