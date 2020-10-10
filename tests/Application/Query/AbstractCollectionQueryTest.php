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

        self::assertSame(0, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testOffset()
    {
        $request = new Request(['offset' => 30]);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(30, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testOffsetNegative()
    {
        $request = new Request(['offset' => PHP_INT_MIN]);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testOffsetHuge()
    {
        $request = new Request(['offset' => PHP_INT_MAX]);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(PHP_INT_MAX, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testLimit()
    {
        $request = new Request(['limit' => 5]);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->offset);
        self::assertSame(5, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testLimitNegative()
    {
        $request = new Request(['limit' => PHP_INT_MIN]);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->offset);
        self::assertSame(1, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testLimitHuge()
    {
        $request = new Request(['limit' => PHP_INT_MAX]);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
    }

    /**
     * @covers ::__construct
     */
    public function testSearch()
    {
        $request = new Request([], [], [], [], [], ['HTTP_X-Search' => 'mAn']);

        $query = new class($request) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertSame('mAn', $query->search);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->sort);
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

        self::assertSame(0, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame($filter, $query->filter);
        self::assertSame([], $query->sort);
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

        self::assertSame(0, $query->offset);
        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->limit);
        self::assertNull($query->search);
        self::assertSame([], $query->filter);
        self::assertSame($sort, $query->sort);
    }
}
