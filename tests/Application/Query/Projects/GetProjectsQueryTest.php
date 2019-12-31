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

namespace eTraxis\Application\Query\Projects;

use eTraxis\Entity\Project;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Projects\Handler\GetProjectsHandler
 */
class GetProjectsQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $repository = $this->doctrine->getRepository(Project::class);

        $expected = array_map(function (Project $project) {
            return $project->name;
        }, $repository->findAll());

        $actual = array_map(function (Project $project) {
            return $project->name;
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
            'Molestiae',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 2;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            'Distinctio',
            'Excepturi',
            'Molestiae',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = 3;

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
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
            'Molestiae',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;
        $query->search = 'eSt';

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName()
    {
        $expected = [
            'Distinctio',
            'Molestiae',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->filter = [
            Project::JSON_NAME => 'Ti',
        ];

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByNameNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->filter = [
            Project::JSON_NAME => null,
        ];

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescription()
    {
        $expected = [
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->filter = [
            Project::JSON_DESCRIPTION => ' d',
        ];

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescriptionNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->filter = [
            Project::JSON_DESCRIPTION => null,
        ];

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterBySuspended()
    {
        $expected = [
            'Excepturi',
            'Molestiae',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->filter = [
            Project::JSON_SUSPENDED => false,
        ];

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testCombinedFilter()
    {
        $expected = [
            'Excepturi',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->filter = [
            Project::JSON_NAME      => 'R',
            Project::JSON_SUSPENDED => false,
        ];

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByName()
    {
        $expected = [
            'Distinctio',
            'Excepturi',
            'Molestiae',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->sort = [
            Project::JSON_NAME => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription()
    {
        $expected = [
            'Distinctio',
            'Molestiae',
            'Excepturi',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->sort = [
            Project::JSON_DESCRIPTION => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByCreated()
    {
        $expected = [
            'Distinctio',
            'Molestiae',
            'Excepturi',
            'Presto',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->sort = [
            Project::JSON_CREATED => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortBySuspended()
    {
        $expected = [
            'Excepturi',
            'Molestiae',
            'Presto',
            'Distinctio',
        ];

        $this->loginAs('admin@example.com');

        $query = new GetProjectsQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetProjectsQuery::MAX_LIMIT;

        $query->sort = [
            Project::JSON_SUSPENDED => GetProjectsQuery::SORT_ASC,
            Project::JSON_NAME      => GetProjectsQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $query = new GetProjectsQuery(new Request());

        $this->queryBus->execute($query);
    }
}
