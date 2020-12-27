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

namespace eTraxis\Application\Query;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \eTraxis\Application\Query\AbstractCollectionQuery
 */
class AbstractCollectionQueryTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testDefaults()
    {
        $request = new Request();

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testOffset()
    {
        $request = new Request(['offset' => 30]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(30, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testOffsetNegative()
    {
        $request = new Request(['offset' => PHP_INT_MIN]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testOffsetHuge()
    {
        $request = new Request(['offset' => PHP_INT_MAX]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(PHP_INT_MAX, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testLimit()
    {
        $request = new Request(['limit' => 5]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(5, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testLimitNegative()
    {
        $request = new Request(['limit' => PHP_INT_MIN]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(1, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testLimitHuge()
    {
        $request = new Request(['limit' => PHP_INT_MAX]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testSearch()
    {
        $request = new Request([], [], [], [], [], ['HTTP_X-Search' => 'mAn']);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertSame('mAn', $query->search);
        static::assertSame([], $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testFilter()
    {
        $filter = [
            'email'       => 'eR',
            'description' => 'a*',
        ];

        $request = new Request([], [], [], [], [], ['HTTP_X-Filter' => json_encode($filter)]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame($filter, $query->filter);
        static::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testSort()
    {
        $sort = [
            'provider' => 'DESC',
            'fullname' => 'ASC',
        ];

        $request = new Request([], [], [], [], [], ['HTTP_X-Sort' => json_encode($sort)]);

        $query = new class($request) extends AbstractCollectionQuery {};

        static::assertSame(0, $query->offset);
        static::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        static::assertNull($query->search);
        static::assertSame([], $query->filter);
        static::assertSame($sort, $query->sort);
    }
}
