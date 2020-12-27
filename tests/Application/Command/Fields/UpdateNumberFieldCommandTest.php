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
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateNumberFieldHandler::__invoke
 */
class UpdateNumberFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta']);

        /** @var \eTraxis\Entity\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);

        static::assertSame(0, $facade->getMinimumValue());
        static::assertSame(1000000000, $facade->getMaximumValue());
        static::assertNull($facade->getDefaultValue());

        $command = new UpdateNumberFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'minimum'  => 1,
            'maximum'  => 999999,
            'default'  => 10,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        static::assertSame(1, $facade->getMinimumValue());
        static::assertSame(999999, $facade->getMaximumValue());
        static::assertSame(10, $facade->getDefaultValue());
    }
}
