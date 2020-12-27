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
 * @covers \eTraxis\Application\Command\Fields\Handler\CreateDecimalFieldHandler::__invoke
 */
class CreateDecimalFieldCommandTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        static::assertNull($field);

        $command = new CreateDecimalFieldCommand([
            'state'    => $state->id,
            'name'     => 'Coverage',
            'required' => true,
            'minimum'  => '0.00',
            'maximum'  => '100.00',
            'default'  => '3.1415',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        static::assertNotNull($field);
        static::assertSame($result, $field);
        static::assertSame(FieldType::DECIMAL, $field->type);

        /** @var \eTraxis\Entity\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($this->manager);
        static::assertSame('0', $facade->getMinimumValue());
        static::assertSame('100', $facade->getMaximumValue());
        static::assertSame('3.1415', $facade->getDefaultValue());
    }
}
