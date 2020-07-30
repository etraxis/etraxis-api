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
 * @covers \eTraxis\Application\Command\Fields\Handler\CreateDurationFieldHandler::__invoke
 */
class CreateDurationFieldCommandTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Time']);
        self::assertNull($field);

        $command = new CreateDurationFieldCommand([
            'state'    => $state->id,
            'name'     => 'Time',
            'required' => true,
            'minimum'  => '0:00',
            'maximum'  => '23:59',
            'default'  => '8:00',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Time']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::DURATION, $field->type);

        /** @var \eTraxis\Entity\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame('0:00', $facade->getMinimumValue());
        self::assertSame('23:59', $facade->getMaximumValue());
        self::assertSame('8:00', $facade->getDefaultValue());
    }
}
