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

namespace eTraxis\Application\Query\States;

use eTraxis\Application\Dictionary\StateResponsible;
use eTraxis\Application\Dictionary\StateType;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\States\Handler\GetStatesHandler
 */
class GetStatesQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(27, $collection->to);
        static::assertSame(28, $collection->total);

        $repository = $this->doctrine->getRepository(State::class);

        $expected = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $repository->findAll());

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
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
            'Opened',
            'Resolved',
            'Submitted',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 25;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(25, $collection->from);
        static::assertSame(27, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => $state->name, $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            'Assigned',
            'Completed',
            'Duplicated',
            'New',
            'Opened',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 5;

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(4, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => $state->name, $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch()
    {
        $expected = [
            ['Assigned', 'Distinctio'],
            ['Opened',   'Distinctio'],
            ['Assigned', 'Excepturi'],
            ['Opened',   'Excepturi'],
            ['Assigned', 'Molestiae'],
            ['Opened',   'Molestiae'],
            ['Assigned', 'Presto'],
            ['Opened',   'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;
        $query->search = 'NEd';

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(7, $collection->to);
        static::assertSame(8, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
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
            ['Assigned',   'Distinctio'],
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['New',        'Distinctio'],
            ['Opened',     'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Submitted',  'Distinctio'],
        ];

        $this->loginAs('admin@example.com');

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_PROJECT => $project->id,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(6, $collection->to);
        static::assertSame(7, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_PROJECT => null,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->total);
        static::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplate()
    {
        $expected = [
            ['Opened',    'Distinctio'],
            ['Resolved',  'Distinctio'],
            ['Submitted', 'Distinctio'],
        ];

        $this->loginAs('admin@example.com');

        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support']);

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_TEMPLATE => $template->id,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(2, $collection->to);
        static::assertSame(3, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplateNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_TEMPLATE => null,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->total);
        static::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName()
    {
        $expected = [
            ['Assigned', 'Distinctio'],
            ['New',      'Distinctio'],
            ['Opened',   'Distinctio'],
            ['Assigned', 'Excepturi'],
            ['New',      'Excepturi'],
            ['Opened',   'Excepturi'],
            ['Assigned', 'Molestiae'],
            ['New',      'Molestiae'],
            ['Opened',   'Molestiae'],
            ['Assigned', 'Presto'],
            ['New',      'Presto'],
            ['Opened',   'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_NAME => 'nE',
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(11, $collection->to);
        static::assertSame(12, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
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

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_NAME => null,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->total);
        static::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByType()
    {
        $expected = [
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Duplicated', 'Excepturi'],
            ['Resolved',   'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Duplicated', 'Molestiae'],
            ['Resolved',   'Molestiae'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_TYPE => StateType::FINAL,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(8, $collection->to);
        static::assertSame(9, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTypeNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_TYPE => null,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->total);
        static::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsible()
    {
        $expected = [
            ['Submitted', 'Distinctio'],
            ['Submitted', 'Excepturi'],
            ['Submitted', 'Molestiae'],
            ['Submitted', 'Presto'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_RESPONSIBLE => StateResponsible::KEEP,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(3, $collection->to);
        static::assertSame(4, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetStatesQuery::MAX_LIMIT;

        $query->filter = [
            State::JSON_RESPONSIBLE => null,
        ];

        $query->sort = [
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->total);
        static::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject()
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['New',        'Distinctio'],
            ['Opened',     'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Submitted',  'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Completed',  'Excepturi'],
            ['Duplicated', 'Excepturi'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            State::JSON_PROJECT  => GetStatesQuery::SORT_ASC,
            State::JSON_TEMPLATE => GetStatesQuery::SORT_ASC,
            State::JSON_NAME     => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(9, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByTemplate()
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Assigned',   'Molestiae'],
            ['Assigned',   'Presto'],
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            State::JSON_TEMPLATE => GetStatesQuery::SORT_ASC,
            State::JSON_NAME     => GetStatesQuery::SORT_ASC,
            State::JSON_PROJECT  => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(9, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
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
            ['Assigned',   'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Assigned',   'Molestiae'],
            ['Assigned',   'Presto'],
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(9, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByType()
    {
        $expected = [
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
            ['Duplicated', 'Molestiae'],
            ['Resolved',   'Distinctio'],
            ['Resolved',   'Excepturi'],
            ['Resolved',   'Molestiae'],
            ['New',        'Distinctio'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            State::JSON_TYPE    => GetStatesQuery::SORT_ASC,
            State::JSON_NAME    => GetStatesQuery::SORT_ASC,
            State::JSON_PROJECT => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(9, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
        ], $collection->data);

        static::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByResponsible()
    {
        $expected = [
            ['Assigned',  'Distinctio'],
            ['Assigned',  'Excepturi'],
            ['Assigned',  'Molestiae'],
            ['Assigned',  'Presto'],
            ['Opened',    'Distinctio'],
            ['Opened',    'Excepturi'],
            ['Opened',    'Molestiae'],
            ['Opened',    'Presto'],
            ['Submitted', 'Distinctio'],
            ['Submitted', 'Excepturi'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetStatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 10;

        $query->sort = [
            State::JSON_RESPONSIBLE => GetStatesQuery::SORT_ASC,
            State::JSON_NAME        => GetStatesQuery::SORT_ASC,
            State::JSON_PROJECT     => GetStatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        static::assertSame(0, $collection->from);
        static::assertSame(9, $collection->to);
        static::assertSame(28, $collection->total);

        $actual = array_map(fn (State $state) => [
            $state->name,
            $state->template->project->name,
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

        $query = new GetStatesQuery(new Request());

        $this->queryBus->execute($query);
    }
}
