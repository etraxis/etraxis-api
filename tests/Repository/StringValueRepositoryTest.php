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

namespace eTraxis\Repository;

use eTraxis\Entity\StringValue;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\StringValueRepository
 */
class StringValueRepositoryTest extends TransactionalTestCase
{
    private Contracts\StringValueRepositoryInterface $repository;

    /**
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(StringValue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        static::assertInstanceOf(StringValueRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        $expected = $this->repository->findOneBy(['value' => 'Development task 1']);
        static::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        static::assertSame($expected, $value);
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $expected = 'eTraxis';

        $count = count($this->repository->findAll());

        /** @var StringValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        static::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var StringValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        static::assertSame($value1, $value);
        static::assertSame($expected, $value->value);
        static::assertCount($count + 1, $this->repository->findAll());

        // Second attempt.
        $value2 = $this->repository->get($expected);

        static::assertSame($value1, $value2);
        static::assertCount($count + 1, $this->repository->findAll());
    }
}
