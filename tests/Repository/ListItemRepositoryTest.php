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

namespace eTraxis\Repository;

use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\ListItemRepository
 */
class ListItemRepositoryTest extends TransactionalTestCase
{
    private Contracts\ListItemRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(ListItemRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $expected = $this->repository->findOneBy(['field' => $field, 'text' => 'high']);
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }

    /**
     * @covers ::findAllByField
     */
    public function testFindAllByField()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $items = $this->repository->findAllByField($field);

        $expected = [
            'high',
            'normal',
            'low',
        ];

        $actual = array_map(fn (ListItem $item) => $item->text, $items);

        self::assertCount(3, $items);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findOneByValue
     */
    public function testFindOneByValueSuccess()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 2);

        self::assertInstanceOf(ListItem::class, $item);
        self::assertSame('normal', $item->text);
    }

    /**
     * @covers ::findOneByValue
     */
    public function testFindOneByValueUnknown()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 4);

        self::assertNull($item);
    }

    /**
     * @covers ::findOneByValue
     */
    public function testFindOneByValueWrongField()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 2);

        self::assertNull($item);
    }
}
