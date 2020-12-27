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
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateStringFieldHandler::__invoke
 */
class UpdateStringFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Commit ID']);

        /** @var \eTraxis\Entity\FieldTypes\StringInterface $facade */
        $facade = $field->getFacade($this->manager);

        static::assertSame(40, $facade->getMaximumLength());
        static::assertSame('Git commit ID', $facade->getDefaultValue());
        static::assertNull($facade->getPCRE()->check);
        static::assertNull($facade->getPCRE()->search);
        static::assertNull($facade->getPCRE()->replace);

        $command = new UpdateStringFieldCommand([
            'field'       => $field->id,
            'name'        => $field->name,
            'required'    => $field->isRequired,
            'maxlength'   => 7,
            'default'     => '1234567',
            'pcreCheck'   => '[0-9a-f]+',
            'pcreSearch'  => 'search',
            'pcreReplace' => 'replace',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        static::assertSame(7, $facade->getMaximumLength());
        static::assertSame('1234567', $facade->getDefaultValue());
        static::assertSame('[0-9a-f]+', $facade->getPCRE()->check);
        static::assertSame('search', $facade->getPCRE()->search);
        static::assertSame('replace', $facade->getPCRE()->replace);
    }
}
