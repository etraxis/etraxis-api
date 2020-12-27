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

namespace eTraxis\Application\Query\Groups;

use eTraxis\Entity\Group;
use eTraxis\Entity\Project;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Groups\Handler\GetGroupsHandler
 */
class GetGroupsQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(17, $collection->to);
        static::assertSame(18, $collection->total);

        $repository = $this->doctrine->getRepository(Group::class);

        $expected = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $repository->findAll());

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        sort($expected);
        sort($actual);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset()
    {
        $expected = [
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 10;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(10, $collection->from);
        static::assertSame(17, $collection->to);
        static::assertSame(18, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            ['Clients',         'Clients A'],
            ['Clients',         'Clients B'],
            ['Clients',         'Clients C'],
            ['Clients',         'Clients D'],
            ['Company Clients', null],
            ['Company Staff',   null],
            ['Developers',      'Developers A'],
            ['Developers',      'Developers B'],
            ['Developers',      'Developers C'],
            ['Developers',      'Developers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(9, $collection->to);
        static::assertSame(18, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch()
    {
        $expected = [
            ['Clients',         'Clients A'],
            ['Clients',         'Clients B'],
            ['Clients',         'Clients C'],
            ['Clients',         'Clients D'],
            ['Company Clients', null],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;
        $query->search = 'cliENTs';

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(4, $collection->to);
        static::assertSame(5, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProject()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
        ];

        $this->loginAs('admin@example.com');

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_PROJECT => $project->id,
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(3, $collection->to);
        static::assertSame(4, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectNull()
    {
        $expected = [
            ['Company Clients', null],
            ['Company Staff',   null],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_PROJECT => null,
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(1, $collection->to);
        static::assertSame(2, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName()
    {
        $expected = [
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_NAME => 'eRS',
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(11, $collection->to);
        static::assertSame(12, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByNameNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_NAME => null,
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->total);
        static::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescription()
    {
        $expected = [
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_DESCRIPTION => 'eRS a',
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(2, $collection->to);
        static::assertSame(3, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescriptionNull()
    {
        $expected = [
            ['Company Clients', null],
            ['Company Staff',   null],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_DESCRIPTION => null,
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(1, $collection->to);
        static::assertSame(2, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByGlobal()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->filter = [
            Group::JSON_GLOBAL => false,
        ];

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(15, $collection->to);
        static::assertSame(16, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject()
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
            ['Clients',           'Clients C'],
            ['Developers',        'Developers C'],
            ['Managers',          'Managers C'],
            ['Support Engineers', 'Support Engineers C'],
            ['Clients',           'Clients B'],
            ['Developers',        'Developers B'],
            ['Managers',          'Managers B'],
            ['Support Engineers', 'Support Engineers B'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->sort = [
            Group::JSON_PROJECT     => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(17, $collection->to);
        static::assertSame(18, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByName()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->sort = [
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(17, $collection->to);
        static::assertSame(18, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription()
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->sort = [
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(17, $collection->to);
        static::assertSame(18, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByGlobal()
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetGroupsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetGroupsQuery::MAX_LIMIT;

        $query->sort = [
            Group::JSON_GLOBAL      => GetGroupsQuery::SORT_ASC,
            Group::JSON_NAME        => GetGroupsQuery::SORT_ASC,
            Group::JSON_DESCRIPTION => GetGroupsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(17, $collection->to);
        static::assertSame(18, $collection->total);

        $actual = array_map(fn (Group $group) => [
            $group->name,
            $group->description,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $query = new GetGroupsQuery(new Request());

        $this->queryBus->execute($query);
    }
}
