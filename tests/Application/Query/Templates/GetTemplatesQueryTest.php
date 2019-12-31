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

namespace eTraxis\Application\Query\Templates;

use eTraxis\Entity\Project;
use eTraxis\Entity\Template;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \eTraxis\Application\Query\Templates\Handler\GetTemplatesHandler
 */
class GetTemplatesQueryTest extends WebTestCase
{
    /**
     * @covers ::__invoke
     */
    public function testDefault()
    {
        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $repository = $this->doctrine->getRepository(Template::class);

        $expected = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $repository->findAll());

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 5;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(5, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = 5;

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;
        $query->search = 'd';

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(5, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProject()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
        ];

        $this->loginAs('admin@example.com');

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_PROJECT => $project->id,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_PROJECT => null,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_NAME => 'eNT',
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_NAME => null,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByPrefix()
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_PREFIX => 'rEQ',
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByPrefixNull()
    {
        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_PREFIX => null,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
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
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_DESCRIPTION => ' d',
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_DESCRIPTION => null,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByCriticalAge()
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_CRITICAL => 3,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByCriticalAgeNull()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_CRITICAL => null,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFrozenTime()
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_FROZEN => 7,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFrozenTimeNull()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_FROZEN => null,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByLocked()
    {
        $expected = [
            ['Development', 'Development Task B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->filter = [
            Template::JSON_LOCKED => true,
        ];

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Development', 'Development Task B'],
            ['Support',     'Support Request B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_PROJECT => GetTemplatesQuery::SORT_ASC,
            Template::JSON_NAME    => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_NAME        => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByPrefix()
    {
        $expected = [
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_PREFIX      => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByCritical()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_CRITICAL    => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByFrozen()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_FROZEN      => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByLocked()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
        ];

        $this->loginAs('admin@example.com');

        $query = new GetTemplatesQuery(new Request());

        $query->offset = 0;
        $query->limit  = GetTemplatesQuery::MAX_LIMIT;

        $query->sort = [
            Template::JSON_LOCKED      => GetTemplatesQuery::SORT_ASC,
            Template::JSON_DESCRIPTION => GetTemplatesQuery::SORT_ASC,
        ];

        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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

        $query = new GetTemplatesQuery(new Request());

        $this->queryBus->execute($query);
    }
}
