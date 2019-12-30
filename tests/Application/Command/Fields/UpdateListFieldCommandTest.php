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

use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\Application\Command\Fields\Handler\UpdateListFieldHandler::__invoke
 */
class UpdateListFieldCommandTest extends TransactionalTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $manager;

    /**
     * @var \eTraxis\Repository\Contracts\FieldRepositoryInterface
     */
    private $repository;

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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority']);

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['field' => $field]);

        /** @var \eTraxis\Entity\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertNotSame($item, $facade->getDefaultValue());

        $command = new UpdateListFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'default'  => $item->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame($item, $facade->getDefaultValue());
    }
}