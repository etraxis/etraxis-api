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
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\CreateDateFieldHandler::__invoke
 */
class CreateDateFieldCommandTest extends TransactionalTestCase
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

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Deadline']);
        self::assertNull($field);

        $command = new CreateDateFieldCommand([
            'state'    => $state->id,
            'name'     => 'Deadline',
            'required' => true,
            'minimum'  => 0,
            'maximum'  => 7,
            'default'  => 3,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Deadline']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::DATE, $field->type);

        /** @var \eTraxis\Entity\FieldTypes\DateInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame(0, $facade->getMinimumValue());
        self::assertSame(7, $facade->getMaximumValue());
        self::assertSame(3, $facade->getDefaultValue());
    }
}
