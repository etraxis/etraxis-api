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
use eTraxis\Application\Command\Fields\Handler\HandlerTrait\TextHandlerTrait;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\TextValue;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Application\Command\Fields\Handler\HandlerTrait\TextHandlerTrait
 */
class TextHandlerTraitTest extends TransactionalTestCase
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
            use TextHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::TEXT, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        /** @var \eTraxis\Entity\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(TextValue::MAX_VALUE, $facade->getMaximumLength());
        self::assertSame('How to reproduce:', $facade->getDefaultValue());
        self::assertNull($facade->getPCRE()->check);
        self::assertNull($facade->getPCRE()->search);
        self::assertNull($facade->getPCRE()->replace);

        $command = new Command\UpdateTextFieldCommand([
            'maxlength'   => 20,
            'default'     => '123-456-7890',
            'pcreCheck'   => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'  => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace' => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame(20, $facade->getMaximumLength());
        self::assertSame('123-456-7890', $facade->getDefaultValue());
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->check);
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->search);
        self::assertSame('($1) $2-$3', $facade->getPCRE()->replace);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldDefaultValueLengthError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should not be longer than 10 characters.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maxlength'   => 10,
            'default'     => '123-456-7890',
            'pcreCheck'   => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'  => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace' => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldDefaultValueFormatError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid format of the default value.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maxlength'   => 20,
            'default'     => '1234567890',
            'pcreCheck'   => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'  => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace' => '($1) $2-$3',
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
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

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

        $command = new Command\UpdateTextFieldCommand([
            'maxlength'   => 20,
            'default'     => '123-456-7890',
            'pcreCheck'   => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'  => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace' => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
