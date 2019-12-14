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

use eTraxis\Entity\TextValue;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\TextValueRepository
 */
class TextValueRepositoryTest extends TransactionalTestCase
{
    /**
     * @var Contracts\TextValueRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(TextValue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(TextValueRepository::class, $this->repository);
    }

    /**
     * @covers ::get
     */
    public function testFindOne()
    {
        $expected = 'Issue tracking system with customizable workflows.';

        $count = count($this->repository->findAll());

        /** @var TextValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var TextValue $value */
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
