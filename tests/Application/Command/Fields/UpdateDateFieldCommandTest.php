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
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateDateFieldHandler::__invoke
 */
class UpdateDateFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Due date']);

        /** @var \eTraxis\Entity\FieldTypes\DateInterface $facade */
        $facade = $field->getFacade($this->manager);

        static::assertSame(0, $facade->getMinimumValue());
        static::assertSame(14, $facade->getMaximumValue());
        static::assertSame(14, $facade->getDefaultValue());

        $command = new UpdateDateFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'minimum'  => 1,
            'maximum'  => 7,
            'default'  => 3,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        static::assertSame(1, $facade->getMinimumValue());
        static::assertSame(7, $facade->getMaximumValue());
        static::assertSame(3, $facade->getDefaultValue());
    }
}
