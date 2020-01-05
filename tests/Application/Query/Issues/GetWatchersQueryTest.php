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

namespace eTraxis\Application\Query\Issues;

use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\Entity\Watcher;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Issues\Handler\GetWatchersHandler
 */
class GetWatchersQueryTest extends TransactionalTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue = $issue->id;

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
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
            'tmarquardt@example.com',
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 1;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(1, $collection->from);
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
    public function testLimit()
    {
        $expected = [
            'fdooley@example.com',
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = 1;

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(2, $collection->total);

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
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;
        $query->search = 'mARq';

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

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
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
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
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

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
        ];

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
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
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

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
            'tmarquardt@example.com',
            'fdooley@example.com',
        ];

        $this->loginAs('ldoyle@example.com');

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->sort = [
            User::JSON_EMAIL => GetWatchersQuery::SORT_DESC,
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

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue  = $issue->id;
        $query->offset = 0;
        $query->limit  = GetWatchersQuery::MAX_LIMIT;

        $query->sort = [
            User::JSON_FULLNAME => GetWatchersQuery::SORT_ASC,
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
    public function testAccessDeniedAnonymous()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs(null);

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue = $issue->id;

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDeniedPermissions()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('aschinner@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $query = new GetWatchersQuery(new Request());

        $query->issue = $issue->id;

        $this->queryBus->execute($query);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('aschinner@example.com');

        $query = new GetWatchersQuery(new Request());

        $query->issue = self::UNKNOWN_ENTITY_ID;

        $this->queryBus->execute($query);
    }
}
