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

namespace eTraxis\Application\Query\Watchers;

use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\Entity\Watcher;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Watchers\Handler\GetWatchersHandler
 */
class GetWatchersQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('artem@example.com');

        $query = new GetWatchersQuery(new Request());

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(26, $collection->to);
        self::assertSame(27, $collection->total);

        $repository = $this->doctrine->getRepository(Watcher::class);

        $expected = array_map(function (Watcher $watcher) {
            return [$watcher->issue->subject, $watcher->user->email];
        }, $repository->findAll());

        $actual = array_map(function (Watcher $watcher) {
            return [$watcher->issue->subject, $watcher->user->email];
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset()
    {
        $expected = [
            'fdooley@example.com',
            'fdooley@example.com',
            'fdooley@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        $query = new GetWatchersQuery(new Request());

        $query->offset = 15;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->filter = [
        ];

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(15, $collection->from);
        self::assertSame(26, $collection->to);
        self::assertSame(27, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'fdooley@example.com',
        ];

        $this->loginAs('artem@example.com');

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_DESC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(27, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;
        $query->search = 'mARq';

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterById()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_ID => $issue->id,
        ];

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByEmail()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_EMAIL => 'mARq',
        ];

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFullname()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->filter = [
            User::JSON_FULLNAME => 'rAcY',
        ];

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByEmail()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_ID => $issue->id,
        ];

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByFullname()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('artem@example.com');

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->filter = [
            Issue::JSON_ID => $issue->id,
        ];

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs(null);

        $query = new GetWatchersQuery(new Request());

        $this->queryBus->execute($query);
    }
}
