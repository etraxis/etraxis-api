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
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateDecimalFieldHandler::__invoke
 */
class UpdateDecimalFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Test coverage']);

        /** @var \eTraxis\Entity\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($this->manager);

        static::assertSame('0', $facade->getMinimumValue());
        static::assertSame('100', $facade->getMaximumValue());
        static::assertNull($facade->getDefaultValue());

        $command = new UpdateDecimalFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'minimum'  => '0.01',
            'maximum'  => '99.99',
            'default'  => '50.00',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        static::assertSame('0.01', $facade->getMinimumValue());
        static::assertSame('99.99', $facade->getMaximumValue());
        static::assertSame('50', $facade->getDefaultValue());
    }
}
