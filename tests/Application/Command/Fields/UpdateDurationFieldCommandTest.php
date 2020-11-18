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
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateDurationFieldHandler::__invoke
 */
class UpdateDurationFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort']);

        /** @var \eTraxis\Entity\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame('0:00', $facade->getMinimumValue());
        self::assertSame('999999:59', $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new UpdateDurationFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'minimum'  => '1:00',
            'maximum'  => '8:00',
            'default'  => '1:30',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame('1:00', $facade->getMinimumValue());
        self::assertSame('8:00', $facade->getMaximumValue());
        self::assertSame('1:30', $facade->getDefaultValue());
    }
}
