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
use eTraxis\Application\Command\Fields\Handler\HandlerTrait\StringHandlerTrait;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\ReflectionTrait;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Application\Command\Fields\Handler\HandlerTrait\StringHandlerTrait
 */
class StringHandlerTraitTest extends TransactionalTestCase
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
            use StringHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        static::assertSame(FieldType::STRING, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        /** @var \eTraxis\Entity\FieldTypes\StringInterface $facade */
        $facade = $field->getFacade($this->manager);

        static::assertSame(40, $facade->getMaximumLength());
        static::assertSame('Git commit ID', $facade->getDefaultValue());
        static::assertNull($facade->getPCRE()->check);
        static::assertNull($facade->getPCRE()->search);
        static::assertNull($facade->getPCRE()->replace);

        $command = new Command\UpdateStringFieldCommand([
            'maxlength'   => 20,
            'default'     => '123-456-7890',
            'pcreCheck'   => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'  => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace' => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        static::assertSame(20, $facade->getMaximumLength());
        static::assertSame('123-456-7890', $facade->getDefaultValue());
        static::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->check);
        static::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->search);
        static::assertSame('($1) $2-$3', $facade->getPCRE()->replace);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldDefaultValueLengthError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should not be longer than 10 characters.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        $command = new Command\UpdateStringFieldCommand([
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
        [$field] = $this->repository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        $command = new Command\UpdateStringFieldCommand([
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
        [$field] = $this->repository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

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

        $command = new Command\UpdateStringFieldCommand([
            'maxlength'   => 20,
            'default'     => '123-456-7890',
            'pcreCheck'   => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'  => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace' => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
