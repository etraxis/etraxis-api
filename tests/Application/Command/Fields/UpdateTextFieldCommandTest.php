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

namespace eTraxis\Application\Command\Fields;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Entity\Field;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateTextFieldHandler::__invoke
 */
class UpdateTextFieldCommandTest extends TransactionalTestCase
{
    private EntityManagerInterface   $manager;
    private FieldRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Description']);

        /** @var \eTraxis\Entity\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($this->manager);

        static::assertSame(10000, $facade->getMaximumLength());
        static::assertSame('How to reproduce:', $facade->getDefaultValue());
        static::assertNull($facade->getPCRE()->check);
        static::assertNull($facade->getPCRE()->search);
        static::assertNull($facade->getPCRE()->replace);

        $command = new UpdateTextFieldCommand([
            'field'       => $field->id,
            'name'        => $field->name,
            'required'    => $field->isRequired,
            'maxlength'   => 2000,
            'default'     => 'How to replicate:',
            'pcreCheck'   => '.+',
            'pcreSearch'  => 'search',
            'pcreReplace' => 'replace',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        static::assertSame(2000, $facade->getMaximumLength());
        static::assertSame('How to replicate:', $facade->getDefaultValue());
        static::assertSame('.+', $facade->getPCRE()->check);
        static::assertSame('search', $facade->getPCRE()->search);
        static::assertSame('replace', $facade->getPCRE()->replace);
    }
}
