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

use eTraxis\Entity\StringValue;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\StringValueRepository
 */
class StringValueRepositoryTest extends TransactionalTestCase
{
    /**
     * @var Contracts\StringValueRepositoryInterface
     */
    private $repository;

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
        self::assertInstanceOf(StringValueRepository::class, $this->repository);
    }

    /**
     * @covers ::get
     */
    public function testFindOne()
    {
        $expected = 'eTraxis';

        $count = count($this->repository->findAll());

        /** @var StringValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var StringValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertSame($value1, $value);
        self::assertSame($expected, $value->value);
        self::assertCount($count + 1, $this->repository->findAll());

        // Second attempt.
        $value2 = $this->repository->get($expected);

        self::assertSame($value1, $value2);
        self::assertCount($count + 1, $this->repository->findAll());
    }
}
