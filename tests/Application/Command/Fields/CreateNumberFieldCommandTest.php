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

namespace eTraxis\Application\Command\Fields;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Application\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\Repository\Contracts\FieldRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\CreateNumberFieldHandler::__invoke
 */
class CreateNumberFieldCommandTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Week number']);
        self::assertNull($field);

        $command = new CreateNumberFieldCommand([
            'state'    => $state->id,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Week number']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::NUMBER, $field->type);

        /** @var \eTraxis\Entity\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame(1, $facade->getMinimumValue());
        self::assertSame(53, $facade->getMaximumValue());
        self::assertSame(7, $facade->getDefaultValue());
    }
}
